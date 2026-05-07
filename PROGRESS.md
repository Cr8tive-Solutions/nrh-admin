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
