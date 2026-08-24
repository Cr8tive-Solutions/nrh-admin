# NRH Portals — Deployment & Hardening Checklist

Two Laravel apps sharing one Supabase Postgres DB:
- **Admin** (`nrh-admin`) → `admin.nrhintelligence.com`
- **Client** (`nrh-intelligence`) → `app.nrhintelligence.com`

Last updated: 2026-08-24. Ordered by when each item must happen.

---

## 1. Blocking — must be done for the recent work to function in production

These come directly from the cross-portal alignment fixes. Skipping them re-breaks features that pass in tests but rely on server config.

- [ ] **Shared storage mount.** The two apps read each other's private files through mount disks. In production both must resolve to the *same* physical storage (shared volume / bucket mount), not sibling directories.
  - Admin `.env`: `CLIENT_STORAGE_PATH=/abs/path/to/client/storage/app/private`
  - Client `.env`: `ADMIN_STORAGE_PATH=/abs/path/to/admin/storage/app/private`
  - Verify: as an admin, download a customer-uploaded payment receipt and a candidate document; as a customer, download a report PDF. All three must stream, not 404.
- [ ] **Hashid salt.** Set a dedicated, stable `HASHIDS_SALT` in **each** app's `.env` (any long random string; the two apps may differ — hashids are app-local by design). Do this **before** first launch or before any `APP_KEY` rotation, then never change it — changing it invalidates every bookmarked/emailed URL. If left unset it falls back to `APP_KEY`.
- [ ] **Scheduler running.** The overdue-invoice flip and daily notifications are scheduled jobs. Ensure the Laravel scheduler cron is installed on the admin app host:
  `* * * * * cd /path/to/nrh-admin && php artisan schedule:run >> /dev/null 2>&1`
  Confirms: `php artisan schedule:list` shows `invoices:mark-overdue` (07:30 MYT), `notifications:generate` (08:00), `pdpa:purge-expired` (02:30), `holidays:sync`.
- [ ] **Client `storage:link`.** Run `php artisan storage:link` in the client app — without it, customer/staff avatars 404 (the public disk symlink is missing).
- [ ] **Caches rebuilt on deploy.** After pulling: `composer install --no-dev -o`, then `php artisan config:cache route:cache view:cache event:cache`. Note: `composer dump-autoload -o` is required whenever `app/Support/helpers.php` autoload changes, or the running PHP-FPM will throw `Call to undefined function hid()`.

---

## 2. Security — before handling real candidate PII (Malaysian NRIC/passport scans)

From the 2026-08-24 PII audit. The request path is sound (authenticated endpoints, tenant scoping, no path traversal); these are lifecycle gaps. **None are fixed yet** — decide scope and I can implement.

### Critical (do before onboarding real candidates)
- [x] **PDPA erasure now deletes files.** ✅ Implemented 2026-08-24. `RedactionService::redactCandidate()` deletes candidate documents (rows + files on `client_local`/`local`), nulls+unlinks `consent_records.evidence_file_path` (keeps the row as proof-of-consent), clears the blind-index hash, and audit-logs `files_deleted`/`files_failed`. Runs for both the retention purge (`pdpa:purge-expired`) and DSAR erasure. Covered by `tests/Feature/PdpaSecurityTest.php`. *(Payment slips/receipts are financial records under separate retention and are intentionally out of scope for per-candidate erasure.)* Still TODO: an orphan-file reconciliation sweep for files left by pre-fix redactions.
- [x] **`identity_number` encryption at rest — implemented, activate on deploy.** ✅ Code done 2026-08-24; **OFF until you set `PII_KEY`.** It's env-gated and backward-compatible so it deploys with zero behaviour change, then activates as a controlled step:
  1. **Deploy the code + run the migration** in one window. Migration `2026_08_24_000001_add_identity_number_hash_and_widen` widens `identity_number` to `text` and adds the `identity_number_hash` blind index (idempotent — safe even though both apps run it against the one shared DB). Run it from **one** app.
  2. **Set the SAME `PII_KEY`** in **both** apps' `.env` (generate with `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"`). Both apps must have it before either encrypts, since they share the DB but have different `APP_KEY`s — that's why encryption uses a dedicated shared key, not `APP_KEY`. Never change it once set.
  3. **Backfill:** `php artisan pii:backfill-identity` (run once; `--dry-run` first). Encrypts existing plaintext rows and populates the blind index.
  - Behaviour change once active: the client "Track" search matches IC by **exact** value (via the blind index) instead of substring — ciphertext can't be substring-searched. Name/reference search is unchanged. Covered by `IdentityEncryptionTest` (client) + `PdpaSecurityTest` (admin).
  - Still recommended alongside: `SESSION_ENCRYPT=true` (session rows are in the same Postgres), and SSE/encrypted-backup storage for the file store.

### High (do in the first hardening pass)
- [ ] **Gate the candidate-document & report download routes.** They currently sit in the "any admin role incl. viewer" block — a zero-permission viewer or a finance user can pull every IC scan. Wrap them in `admin.can:` behind a new `pii.documents` permission (or reuse `pdpa.consent`), granted to operations + super_admin only.
- [ ] **Audit-log every admin file download.** None of the six admin download handlers log access; you can't answer "whose IC scans were exfiltrated" after an incident. Add `AdminAuditLog::record('pii.document_downloaded', …)` with document/candidate/request/admin id + IP.
- [ ] **Revalidate admin status on each request.** A deactivated admin keeps full access until their session expires (`AdminAuth` checks only `session('admin_id')`; status is checked only at login). Revalidate `status === 'active'` in the middleware and purge that admin's `sessions` rows on deactivation.

### Medium
- [ ] Set `SESSION_SECURE_COOKIE=true` in both apps; add `Strict-Transport-Security` and a CSP to `SecurityHeaders`; force HTTPS behind the proxy (`URL::forceScheme('https')` + TrustProxies).
- [ ] Set `'serve' => false` on the private `local` disk in both apps (nothing uses the serve route; it exposes a signature-gated PUT into private storage).
- [ ] Store a `disk` column per file row instead of the two-disk fallback guess, and validate the stored path matches the expected prefix for that record + customer.
- [ ] Derive candidate-document stored extension from `guessExtension()` against an allow-list and add a random filename suffix (paths are currently guessable from the sequential `REQ-YYYY-NNNN` reference).

---

## 3. Data & config sanity

- [ ] **Change seeded credentials.** `AdminSeeder` ships `admin@nrhintelligence.com` / `Admin@1234` and `ops@…`. Rotate or remove before production. Same for any demo customer users.
- [ ] **Confirm SST rate.** `config('billing.sst_rate')` = 0.06 in both apps — verify against current Malaysian rate at go-live.
- [ ] **Price-on-request scopes.** With the new guard, customers can't submit an unpriced price-on-request scope and admin invoicing skips unpriced lines with a warning. Ensure `customer_scope_prices` is populated for every Malaysia scope each customer is expected to order.
- [ ] **Legacy invoice numbers** (optional). Mixed 3-/4-digit `INV-` numbers sort correctly as-is. Only run `php artisan invoices:normalize-numbers --apply` if the business accepts that already-issued PDFs keep their old number. (Decided: leave as-is.)
- [ ] **PDF fonts.** Run `php artisan fonts:register` on the admin host after deploy (font metric files contain absolute paths and must be regenerated per server).

---

## 4. Post-deploy smoke test

Run against the **real** server (in this environment the automation browser hits a stale backend — verify with curl or a real browser session, not the automation Chrome):

- [ ] Admin + client login (rotate the 2FA on a real admin).
- [ ] Customer submits a per-request (cash) request → sees SST-inclusive total → uploads slip → admin verifies → request flips to `in_progress`, transaction booked = SST-inclusive amount.
- [ ] Admin generates a full report → customer downloads the PDF (exercises the `admin_local` mount).
- [ ] Admin opens a customer-uploaded candidate document (exercises `client_local`).
- [ ] Let an invoice pass its due date (or run `invoices:mark-overdue`) → both portals show the red "Overdue" badge.
- [ ] Customer page shows "Prepaid credit" derived from the transactions ledger.

---

## Notes carried forward
- Admin-invited *secondary* customer users activate with zero Spatie roles by design — the customer's Owner assigns roles via Settings → Users.
- Tests: `composer test` in each repo (89 admin, 31 client at last run). Admin needs local Postgres `nrh_test`; the suite pins `memory_limit=512M` for the dompdf report tests.
