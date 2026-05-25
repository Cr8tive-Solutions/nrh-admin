# NRH Admin — Progress

## 2026-05-07

### Shipped (commit `9202f28`)

**Billing Mode**
- Normalised `agreements.billing` to canonical values (`monthly` / `per_request`).
- Replaced free-text input with a required `<select>` on the agreement create/edit forms.
- Customer detail page shows a green "Credit" or gold "Cash" billing chip.
- Added a "Confirm Payment" workflow on the admin request detail page for cash-billed requests — records a transaction and flips status `new → in_progress`.
- New `Agreement::billingMode()`, `isPerRequest()`, `isMonthly()` helpers.
- New `ScreeningRequest::calculateTotal()` — sums effective scope prices using `customer_scope_prices` overrides where present.

**Handoff #1 — Signed-consent flag**
- Added `scope_types.requires_signed_consent` boolean.
- Amber checkbox + helper copy on the scope create/edit forms.
- Inline pill on the scopes index when the flag is set.
- Admin's existing consent records UI now labels paper-signed evidence as "View signed consent form →".

**Handoff #2 — Status expansion**
- Expanded `screening_requests.status` enum to add `rejected`, `prelim`, `updated` (Postgres CHECK constraint dropped + recreated).
- Added `screening_requests.rejection_reason` text column.
- Updated `ScreeningRequest::STATUSES`, `statusBadgeClass()` (rejected→red, prelim→black, updated→green, flagged→amber), and `isTatPaused()` (true when rejected).
- `RequestQueueController::updateStatus()` now requires `rejection_reason` when status=rejected and auto-clears it on transition away.
- `ReportController::generate()` auto-flips status: prelim type → `prelim`; full type → `complete` (or `updated` if previously complete).
- Request detail view: TAT-paused pill, rejection-reason form, customer-visible read-only panel when rejected.

**Handoff #3 — Payment receipts**
- New `invoice_payment_receipts` table + `screening_requests.invoice_id` FK.
- New `InvoicePaymentReceipt` model + relations on `Invoice` (`receipts`, `screeningRequests`, `verifiedReceiptsTotal()`).
- New `PaymentReceiptController` with verify / reject / file-download actions, all idempotent (no-op once receipt has left `pending`).
- Verify cascade (single DB transaction): mark verified → write `transactions` row → if coverage hits invoice total flip invoice to `paid` → cascade-flip linked `new` requests to `in_progress` → audit log.
- Reject path: required reason, no downstream effects.
- Invoice detail view: linked-requests panel + receipts panel with inline verify/reject Alpine UI and a live preview of the cascade outcome.
- Smoke-tested end-to-end via tinker: `pending → verified`, `unpaid → paid`, `new → in_progress`, transaction row + audit log written.

### Up next

1. **Statement of Account PDF** — deferred from Handoff #3 (strongly-recommended). Admin generates per-customer monthly PDF (`storage/app/statements/{customer_id}/{yyyy-mm}.pdf`) listing invoices + payments, using the existing dompdf setup. ~30 min.
2. **Retire (or keep) the legacy `confirmPayment` cash-flow** — decide whether the older Confirm Payment button on the request detail page should stay alongside the new receipt-verification flow, or collapse into a single source of truth (receipts only). Currently both coexist; `screening_requests.invoice_id` is nullable specifically to allow this.
3. **Wait on client portal receipt-upload UI** — admin-side panel is empty until they ship. When the first real receipt lands, sanity-check the file path / mime handling and confirm the verify cascade behaves the same on a real upload as it did in the smoke test.

---

## 2026-05-25

### Sync: nrh-admin ↔ nrh-intelligence flow alignment

Audited both portals against the shared database and fixed all mismatches.

**nrh-intelligence (client portal) — status handling:**
- `ScreeningRequest::scopeActive()` expanded to cover all statuses; `scopeComplete()` includes `updated`
- `rejection_reason` added to `$fillable`
- `details.blade.php` — status pills, pipeline tracker, download button, rejection banner all aligned with admin statuses
- `index.blade.php` — tabs/counts updated; Alpine filter maps `updated` → `complete` tab
- `track.blade.php` — step map corrected
- `candidates/show.blade.php` — renders new structured findings format (`result_type`, `risk_level`, `records[]`) with legacy fallback

---

### Report type rename + Basic removal (nrh-admin)

Removed `Basic` report type. Generate panel is now **Prelim | Full | Updated**.

- `ReportVersion::types()` → `['prelim', 'full']`; `label()` shows "Updated vN" for re-issued full reports
- `ReportController` — validation `in:prelim,full`; prelim no longer flips request status (see status simplification below)
- `show.blade.php` — new explicit Prelim / Full / Updated buttons; Updated button opens the supersede modal pre-loaded with the latest full version; `Basic completion` date removed from metadata section
- Report labels in client portal sidebar: `PRELIM`, `FULL`, `UPDATED`

---

### Add candidate to existing request (nrh-intelligence)

New feature: credit (monthly-billed) clients can add candidates to an in-flight request while `invoice_id IS NULL` and request is not complete/updated/rejected.

- New `AddCandidateController` — eligibility enforced server-side
- Route: `POST /requests/{id}/candidates` (requires `create-requests` permission)
- `ViewRequestController::details()` passes `$canAddCandidate`, `$identityTypes`, `$availableScopeTypes`
- `details.blade.php` — "Add candidate" button + Alpine modal with scope checkboxes pre-checked from existing request scopes

---

### Status simplification — removed `prelim` and `flagged` from request status (both portals)

**Decision rationale:**
- `flagged` is redundant at request level — already exists on `request_candidates` and `candidate_scope_type`; derivable via `whereHas`
- `prelim` is a report event, not a workflow state — request stays `in_progress` after prelim report is issued

**Final canonical request statuses: `new → in_progress → complete → updated` + `rejected` (terminal)**

**nrh-admin:**
- `ScreeningRequest::STATUSES` and `statusBadgeClass()` updated
- `ReportController` — prelim report generation no longer flips request status
- `DashboardController` — `flagged_cases` / `$flaggedRequests` now query by candidate status; `$recentRequests` eager-loads candidates
- `CustomerController` — `requests_flagged` stat derived from candidate status
- `show.blade.php` — `$statusMap`, `$currentIndex`, `$isDone` cleaned up
- `index.blade.php` — filter: removed Flagged, added Rejected + Updated
- `dashboard/index.blade.php` — `$isFlag` uses candidate check; "Review now" links to `in_progress`

**nrh-intelligence:**
- `scopeActive()` no longer includes `flagged`/`prelim`
- `DashboardController` — `needs_review` queries by candidate status
- `index.blade.php` — Flagged and Prelim tabs removed
- `details.blade.php` — `$isFlagged` is now purely candidate-derived
- `track.blade.php`, `_status-badge.blade.php`, `dashboard/index.blade.php` — all cleaned up

### Pending

- **DB migration needed:** Existing `screening_requests` rows with `status = 'prelim'` or `status = 'flagged'` should be backfilled to `in_progress`. No migration written yet.
- **CLAUDE.md update:** Status values section still lists the old set — should be updated to the new canonical five.
