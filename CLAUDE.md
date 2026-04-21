# NRH Admin — Claude Code Project Brief
> Drop this file as `CLAUDE.md` in the root of the `nrh-admin` Laravel project.

---

## What This Project Is

**NRH Admin** is the internal operations portal for NRH Intelligence Sdn. Bhd. staff. It is a **separate Laravel project** that shares the same PostgreSQL (Supabase) database as the client-facing portal (`nrh-intelligence`).

- **Client portal** → `nrh-intelligence` project → `app.nrhintelligence.com`
- **Admin portal** → `nrh-admin` project (this) → `admin.nrhintelligence.com`
- **Shared database** → Same Supabase PostgreSQL instance. Both apps read/write the same tables.
- **No shared code** — completely separate Laravel apps. Models are duplicated intentionally.

---

## Technology Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 (PHP 8.5) |
| Database | PostgreSQL via Supabase (Session Pooler) |
| Frontend | Blade, Alpine.js, Tailwind CSS v4 |
| Auth | Session-based (separate from client portal) |
| Local dev | Laravel Herd |

---

## Database Connection

Use the **Supabase Session Pooler** — direct connections fail on IPv4 networks.

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.eyivvzcenwtsongnkvrj
DB_PASSWORD=<ask project owner>
```

> The username format is `postgres.<project-ref>` — this is required for the Session Pooler.

---

## Database Schema (Shared — Do NOT Modify Structure)

All tables below belong to the shared database. The admin portal reads and writes to them. **Never drop or rename columns** — the client portal depends on them.

### `customers`
Corporate client accounts.
```
id, name, registration_no, address, country, industry,
contact_name, contact_email, contact_phone, balance (decimal 10,2),
created_at, updated_at
```

### `customer_users`
Login accounts for client portal users (NOT admin staff).
```
id, customer_id (FK→customers), name, email, password (hashed),
role (admin|user), status (active|inactive), avatar (nullable),
remember_token, created_at, updated_at
```

### `agreements`
Service contracts between NRH and each customer.
```
id, customer_id (FK→customers), type, start_date, expiry_date,
sla_tat, billing, payment, terms (json), created_at, updated_at
```
> `days_left` is a computed accessor — calculate as `MAX(0, expiry_date - today)` in PHP.

### `countries`
Geographic coverage list.
```
id, name, code (3-char), flag (emoji), region, created_at, updated_at
```

### `identity_types`
Document types (MyKAD, Passport, etc.).
```
id, name, created_at, updated_at
```

### `scope_types`
Individual verification services offered.
```
id, country_id (FK→countries), name, category (nullable),
turnaround, price (decimal 8,2), price_on_request (boolean, default false),
description, created_at, updated_at
```
> Malaysia scopes: `price_on_request = true`, price = 0.00 (negotiated per customer via `customer_scope_prices`).
> Singapore/Indonesia/Thailand scopes: fixed price, `price_on_request = false`.

### `customer_scope_prices`
Per-customer custom pricing (overrides `scope_types.price`).
```
id, customer_id (FK→customers), scope_type_id (FK→scope_types),
price (decimal 10,2), created_at, updated_at
UNIQUE (customer_id, scope_type_id)
```

### `packages`
Pre-built scope bundles per customer per country.
```
id, customer_id (FK→customers), country_id (FK→countries),
name, created_at, updated_at
```

### `package_scope_type`
Pivot: packages ↔ scope_types.
```
package_id (FK→packages), scope_type_id (FK→scope_types)
```

### `screening_requests`
Request headers submitted by clients.
```
id, customer_id (FK→customers), customer_user_id (FK→customer_users),
reference (e.g. REQ-2026-0001), status (new|in_progress|flagged|complete),
type (nullable, e.g. "malaysia", "global", "kyc", "kyb", "kys"),
meta (json, nullable), created_at, updated_at
```

### `request_candidates`
Individual candidates under a screening request.
```
id, screening_request_id (FK→screening_requests),
identity_type_id (FK→identity_types),
name, identity_number, mobile (nullable), remarks (nullable),
status (new|in_progress|flagged|complete), created_at, updated_at
```

### `candidate_scope_type`
Pivot: which scope checks are assigned to each candidate + per-check status.
```
request_candidate_id (FK→request_candidates),
scope_type_id (FK→scope_types),
status (varchar)
```

### `invoices`
Monthly billing documents.
```
id, customer_id (FK→customers), number (e.g. INV-2026-001),
period (e.g. "April 2026"), status (unpaid|paid|overdue),
issued_at (date), due_at (date),
subtotal (decimal 10,2), tax (decimal 10,2), total (decimal 10,2),
created_at, updated_at
```

### `invoice_items`
Line items on each invoice.
```
id, invoice_id (FK→invoices), description,
qty (smallint), unit_price (decimal 8,2), total (decimal 10,2),
created_at, updated_at
```

### `transactions`
Payment records.
```
id, customer_id (FK→customers),
type (topup|payment|adjustment),
amount (decimal 10,2), reference (nullable), status,
method (e.g. "Bank Transfer", "Monthly Billing"),
notes (nullable), created_at, updated_at
```

---

## Admin Auth Design

- Admin staff have their **own table** — `admins` — which you must create via migration.
- Do NOT use `customer_users` for admin login.
- Use session-based auth with session keys prefixed `admin_*` (e.g. `admin_id`, `admin_name`, `admin_role`).
- Roles: `super_admin`, `operations`, `finance`, `viewer`.
- Protect all admin routes with a custom `AdminAuth` middleware that checks `session('admin_id')`.
- The `admins` table does not exist yet — create it in your first migration.

Suggested `admins` table:
```
id, name, email (unique), password (hashed), role (enum), status (active|inactive),
avatar (nullable), remember_token, created_at, updated_at
```

Default seed accounts (password: `Admin@1234`):
- `admin@nrhintelligence.com` → role: `super_admin`
- `ops@nrhintelligence.com` → role: `operations`

---

## What the Admin Portal Must Do

### 1. Authentication
- Login page at `/login`
- Email + password (no 2FA required internally)
- Redirect to dashboard on success
- Separate session from client portal

### 2. Dashboard
- Stats: active requests, flagged cases, completed today, total customers, unpaid invoices
- Recent requests table (all customers)
- Pending (new) requests queue

### 3. Request Queue & Processing
- List all screening requests with filter by status (new / in_progress / flagged / complete)
- Search by reference or customer name
- Request detail page:
  - View all candidates + their assigned scopes
  - **Update request status** (new → in_progress → flagged → complete)
  - **Update individual candidate status**
  - Add internal notes (optional)
- When status = `complete`, the client portal automatically shows it as completed

### 4. Customer Management
- List all customers with search
- Customer profile:
  - Company info (name, reg no, address, industry, contact)
  - Service agreement (type, dates, SLA, billing)
  - Team members (customer_users)
  - Recent screening requests
  - Invoice history
  - Transaction history

### 5. Per-Customer Scope Pricing
- View all scope types grouped by country and category
- Set / update custom price for a specific customer per scope
- Stored in `customer_scope_prices` table
- If no custom price exists, `scope_types.price` is used as the default
- Malaysia scopes are always `price_on_request` — admin sets custom price per customer here

### 6. Invoice Management
- List all invoices across all customers
- Create invoice manually:
  - Select customer, set period, due date
  - Add line items (description, qty, unit price)
  - Auto-calculate subtotal + 6% SST tax + total
  - Generate invoice number (INV-YYYY-NNN)
- Mark invoice as paid
- View invoice detail

### 7. Transaction Recording
- Record incoming payments against a customer
- Link to invoice (optional)
- Method: Bank Transfer / Online / Adjustment

### 8. Agreement Management
- Create / edit service agreements per customer
- Fields: type, start date, expiry date, SLA TAT, billing cycle, payment terms

### 9. Staff Management (Super Admin only)
- List admin staff accounts
- Create / deactivate staff
- Assign roles

### 10. System Configuration
- Manage scope types (add/edit/disable)
- Manage countries

---

## Status Values (use exactly these strings)

```
screening_requests.status:   new | in_progress | flagged | complete
request_candidates.status:   new | in_progress | flagged | complete
invoices.status:             unpaid | paid | overdue
transactions.type:           topup | payment | adjustment
customer_users.role:         admin | user
customer_users.status:       active | inactive
```

---

## Screening Request Reference Format

`REQ-YYYY-NNNN` — e.g. `REQ-2026-0042`

Generated by the client portal. Admin reads and displays as-is.

## Invoice Number Format

`INV-YYYY-NNN` — e.g. `INV-2026-001`

Admin generates these when creating invoices.

---

## Key Business Rules

1. **Malaysia scopes** (`scope_types.price_on_request = true`) have no fixed price. Price is always set per customer in `customer_scope_prices`. If no price is set for a customer, show "Price on request".
2. **Tax is 6% SST** — apply to invoice subtotal.
3. **Agreement expiry** — warn at 60 days, critical at 14 days.
4. **Candidate status** is independent of request status. A request can be `complete` even if some candidates are `flagged` — admin decides.
5. **Balance** on `customers` is a credit balance (for prepaid accounts). Not currently used in billing logic but reserved for future.

---

## Malaysia Scope Categories (42 scopes across 13 categories)

1. Security & Integrity Check
2. Anti-Money Laundering & CTF (AML/CTF)
3. Securities Commission (Capital Market)
4. Bursa Malaysia (Corporate Enforcement)
5. Global Sanctions & PEP
6. Financial Standing & Credit Records
7. Legal & Civil Proceedings
8. Driving & Licensing Records
9. International Travel Restriction
10. Corporate Governance & Ownership
11. Digital Presence & Online Risk
12. Academic & Qualification Verification
13. Employment & Reference Verification

---

## Design Guidance

- Use a **dark sidebar** — the admin portal should feel distinct from the client portal
- Content area (cards, tables, buttons) can use the same design language
- Desktop-first — admins work at desks, mobile is not a priority
- Dense tables are preferred — admins need to see more data at once
- Use Tailwind CSS v4 + Alpine.js for interactivity (same as client portal)

---

## Related Project

The client portal (`nrh-intelligence`) is the companion project. Its full system proposal is in `docs/system-proposal.md` — copy it to this project for full context.

---

*NRH Intelligence Sdn. Bhd. — Internal use only*
