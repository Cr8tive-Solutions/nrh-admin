<?php

namespace App\Support;

/**
 * Content source for the Admin Portal Guide (/guide).
 *
 * Kept as structured data rather than markup so the on-screen version and the
 * PDF render from one source and can never drift apart. Both views walk this
 * array through resources/views/docs/_blocks.blade.php.
 *
 * Block types understood by the renderer:
 *   ['p',     'text' => string]                      paragraph (inline HTML allowed)
 *   ['sub',   'text' => string]                      sub-heading
 *   ['steps', 'items' => string[]]                   numbered procedure
 *   ['list',  'items' => string[]]                   bullet list
 *   ['table', 'head' => string[], 'rows' => array[], 'widths' => ?string[]]
 *   ['note',  'title' => string, 'text' => string]   informational callout
 *   ['warn',  'title' => string, 'text' => string]   caution callout
 *   ['links', 'items' => [['label' => string, 'route' => string]]]
 *
 * Text is authored here by developers, never by users, so the renderer prints
 * it unescaped to allow <b>, <code> and <i>.
 */
class AdminGuide
{
    /**
     * @return array<int, array{
     *     id: string, title: string, permission: ?string,
     *     summary: string, blocks: array<int, array<string, mixed>>
     * }>
     */
    public static function sections(): array
    {
        return [
            self::about(),
            self::signingIn(),
            self::rolesAndPermissions(),
            self::navigation(),
            self::dashboard(),
            self::requestQueue(),
            self::findings(),
            self::reports(),
            self::customers(),
            self::pricing(),
            self::invoices(),
            self::transactions(),
            self::configuration(),
            self::compliance(),
            self::staff(),
            self::auditLog(),
            self::account(),
            self::reference(),
            self::troubleshooting(),
        ];
    }

    private static function about(): array
    {
        return [
            'id' => 'about',
            'title' => 'About This Guide',
            'permission' => null,
            'summary' => 'What this document covers and how to move around it.',
            'blocks' => [
                ['type' => 'p', 'text' => 'This is the operating manual for the <b>NRH Admin Portal</b> — the internal staff portal at <code>admin.nrhintelligence.com</code>. It covers every module in the sidebar, the permission each one requires, and the exact effect of the actions you can take.'],
                ['type' => 'p', 'text' => 'The admin portal shares its database with the client portal (<code>app.nrhintelligence.com</code>), which is where customers submit screening requests and upload documents. Anything you change here is visible to customers immediately, so read the callouts on irreversible actions.'],
                ['type' => 'sub', 'text' => 'How to use this document'],
                ['type' => 'list', 'items' => [
                    '<b>The contents page is clickable.</b> Select any entry to jump straight to that section — this works in the PDF as well as on screen.',
                    '<b>Blue links open the live portal.</b> Every section ends with links that take you to the matching page, so you can follow along while you read.',
                    '<b>Each section header shows the permission it needs.</b> If you do not hold that permission the page is hidden from your sidebar and the URL returns a 403.',
                ]],
                ['type' => 'note', 'title' => 'Reading this as a PDF', 'text' => 'Internal jumps and portal links are live in the PDF too. If you print it on paper, the reference tables in Section 18 are the part worth keeping at your desk.'],
            ],
        ];
    }

    private static function signingIn(): array
    {
        return [
            'id' => 'signing-in',
            'title' => 'Signing In & Two-Factor Authentication',
            'permission' => null,
            'summary' => 'Password, TOTP challenge, and recovery codes.',
            'blocks' => [
                ['type' => 'p', 'text' => 'Admin accounts are entirely separate from customer accounts. A customer portal login will not work here, and vice versa — even for the same person.'],
                ['type' => 'sub', 'text' => 'Signing in'],
                ['type' => 'steps', 'items' => [
                    'Go to <code>/login</code> and enter your work email and password.',
                    'If two-factor authentication is enabled on your account you are redirected to <code>/two-factor-challenge</code>. Enter the 6-digit code from your authenticator app.',
                    'If you cannot reach your authenticator, enter one of your recovery codes instead. <b>Each recovery code works once</b> and is consumed as soon as it is used.',
                ]],
                ['type' => 'warn', 'title' => 'Rate limiting', 'text' => 'Both the login form and the two-factor challenge allow 5 attempts per minute. If you trip the limit, wait a minute rather than retrying — repeated attempts extend the lockout.'],
                ['type' => 'sub', 'text' => 'Setting up two-factor authentication'],
                ['type' => 'steps', 'items' => [
                    'Open <b>My Account → Security</b>.',
                    'Select <b>Enable two-factor authentication</b>. A QR code and a set of recovery codes are shown.',
                    'Scan the QR code with your authenticator app (Google Authenticator, Authy, 1Password, or similar).',
                    'Enter the 6-digit code from the app to confirm. Setup is not complete until you confirm — until then you can cancel and start again.',
                    '<b>Save your recovery codes somewhere safe and offline.</b> They are the only way back in if you lose your phone.',
                ]],
                ['type' => 'p', 'text' => 'You can regenerate recovery codes at any time from the same page. Regenerating invalidates every previously issued code.'],
                ['type' => 'note', 'title' => 'Locked out?', 'text' => 'A colleague with the <code>staff.manage</code> permission can reset your two-factor from <b>Staff → Reset 2FA</b>. This clears your authenticator binding so you can enrol a fresh device on your next sign-in. It does not reset your password.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'My Account → Security', 'route' => 'account.security'],
                ]],
            ],
        ];
    }

    private static function rolesAndPermissions(): array
    {
        return [
            'id' => 'roles',
            'title' => 'Roles & Permissions',
            'permission' => null,
            'summary' => 'How access is decided, and what each permission key unlocks.',
            'blocks' => [
                ['type' => 'p', 'text' => 'Access is decided by <b>permissions</b>, not directly by role. Your role carries a default set of permissions, and an administrator can grant or revoke individual permissions on top of that for your account specifically. The result is your <i>effective</i> permission set.'],
                ['type' => 'table', 'head' => ['Role', 'Typically used for'], 'widths' => ['26%', '74%'], 'rows' => [
                    ['<code>super_admin</code>', 'Full access. Bypasses every permission check, including ones not yet defined.'],
                    ['<code>operations</code>', 'Screening analysts — work the request queue, record findings, issue reports.'],
                    ['<code>finance</code>', 'Invoicing, payment verification, transactions and pricing.'],
                    ['<code>viewer</code>', 'Read-only oversight. Can open lists and request details but change nothing.'],
                ]],
                ['type' => 'sub', 'text' => 'Permission keys'],
                ['type' => 'table', 'head' => ['Permission key', 'Grants'], 'widths' => ['30%', '70%'], 'rows' => [
                    ['<code>report.view</code>', 'Preview the live report and view or download issued report PDFs.'],
                    ['<code>request.update</code>', 'Change request, candidate and scope statuses; edit findings and report metadata; issue reports.'],
                    ['<code>customer.manage</code>', 'Create and edit customers, provision and re-invite customer users, manage agreements.'],
                    ['<code>pricing.manage</code>', 'Set per-customer scope prices.'],
                    ['<code>invoice.manage</code>', 'Create, bulk-generate and edit invoices; mark invoices paid.'],
                    ['<code>transaction.manage</code>', 'Record transactions; verify or reject customer payment slips and receipts.'],
                    ['<code>config.scopes</code>', 'Manage scope types and business holidays.'],
                    ['<code>config.countries</code>', 'Manage the country list.'],
                    ['<code>staff.manage</code>', 'Create staff, activate/deactivate accounts, set per-user permissions, reset 2FA, view the audit log.'],
                    ['<code>permissions.manage</code>', 'Edit the role → permission matrix.'],
                    ['<code>pdpa.consent</code>', 'Record candidate consent and download consent evidence.'],
                    ['<code>pdpa.dsar</code>', 'Handle Data Subject Requests, including erasure.'],
                    ['<code>pdpa.retention</code>', 'Configure retention policies and run manual purges.'],
                    ['<code>pdpa.documents</code>', 'Download customer-uploaded candidate documents (identity documents, resumes, consent forms).'],
                ]],
                ['type' => 'note', 'title' => 'Why some pages are missing from your sidebar', 'text' => 'The sidebar only renders links you have permission to use. A missing entry is a permissions question, not a fault — ask a <code>super_admin</code> or anyone with <code>staff.manage</code>.'],
                ['type' => 'warn', 'title' => 'Candidate documents are gated separately', 'text' => 'Raw identity documents are the most sensitive data in the system, so <code>pdpa.documents</code> is deliberately not bundled into read access. Viewer and finance roles can see that a document exists without being able to open it.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Roles & Permissions matrix', 'route' => 'permissions.index'],
                ]],
            ],
        ];
    }

    private static function navigation(): array
    {
        return [
            'id' => 'navigation',
            'title' => 'Navigating the Portal',
            'permission' => null,
            'summary' => 'Sidebar layout, notifications, theme and account menu.',
            'blocks' => [
                ['type' => 'p', 'text' => 'The sidebar is grouped into four bands. Entries appear only if your permissions allow them.'],
                ['type' => 'table', 'head' => ['Band', 'Contains'], 'widths' => ['24%', '76%'], 'rows' => [
                    ['<b>Operations</b>', 'Dashboard, Request Queue, Customers'],
                    ['<b>Finance</b>', 'Scope Pricing, Invoices, Transactions'],
                    ['<b>Configuration</b>', 'Scope Types, Countries, Business Holidays'],
                    ['<b>Compliance &amp; Admin</b>', 'Data Subject Requests, Data Retention, Staff, Audit Log, Roles &amp; Permissions'],
                ]],
                ['type' => 'list', 'items' => [
                    '<b>Request Queue badge</b> — the red count next to Request Queue is the number of requests waiting on you. It updates on every page load.',
                    '<b>Notifications</b> — the bell in the top bar. Select an item to mark it read, or use <b>Mark all as read</b>. A nightly job posts the day\'s notifications at 08:00 MYT.',
                    '<b>Dark mode</b> — the toggle in the top bar. Your choice is remembered in the browser, per device.',
                    '<b>Account</b> — your name and avatar at the bottom of the sidebar open your profile. <b>Sign out</b> sits directly below it.',
                ]],
                ['type' => 'links', 'items' => [
                    ['label' => 'Notifications', 'route' => 'notifications.index'],
                ]],
            ],
        ];
    }

    private static function dashboard(): array
    {
        return [
            'id' => 'dashboard',
            'title' => 'Dashboard',
            'permission' => null,
            'summary' => 'The daily overview — figures, what each one counts, and where to act.',
            'blocks' => [
                ['type' => 'p', 'text' => 'The dashboard is visible to every signed-in admin regardless of role. It is a read-only summary; every panel links through to the page where you act on it.'],
                ['type' => 'table', 'head' => ['Figure', 'Exactly what it counts'], 'widths' => ['32%', '68%'], 'rows' => [
                    ['<b>Total cleared</b>', 'Requests with status <code>complete</code>.'],
                    ['<b>Active requests</b>', 'Requests with status <code>new</code> or <code>in_progress</code>.'],
                    ['<b>Flagged cases</b>', 'Requests that have at least one candidate with status <code>flagged</code> — regardless of the request\'s own status.'],
                    ['<b>Total customers</b>', 'All customer accounts.'],
                    ['<b>Unpaid invoices</b>', 'Invoices with status <code>unpaid</code>. Invoices already flipped to <code>overdue</code> are <i>not</i> included here.'],
                ]],
                ['type' => 'sub', 'text' => 'Panels'],
                ['type' => 'list', 'items' => [
                    '<b>Weekly volume</b> — requests created per day over the last 7 days, including today.',
                    '<b>Expiring agreements</b> — the next 8 agreements expiring within 60 days. Treat 60 days as a warning and 14 days as critical.',
                    '<b>Flagged requests</b> — the 5 most recent requests carrying a flagged candidate. These usually need an analyst\'s judgement before a report goes out.',
                    '<b>Recent requests</b> — the 8 newest requests of any status.',
                ]],
                ['type' => 'links', 'items' => [
                    ['label' => 'Open the Dashboard', 'route' => 'dashboard'],
                ]],
            ],
        ];
    }

    private static function requestQueue(): array
    {
        $sst = rtrim(rtrim(number_format((float) config('billing.sst_rate', 0.06) * 100, 2), '0'), '.');

        return [
            'id' => 'requests',
            'title' => 'Request Queue',
            'permission' => 'Read for all roles; changes need request.update',
            'summary' => 'The core screening workflow, from intake to completion.',
            'blocks' => [
                ['type' => 'p', 'text' => 'A <b>screening request</b> comes in from the client portal with a reference like <code>REP-2026-0184</code>. It belongs to one customer, was raised by one of that customer\'s users, and contains one or more <b>candidates</b>. Each candidate carries a set of <b>scopes</b> — the individual checks to be run.'],
                ['type' => 'sub', 'text' => 'The queue list'],
                ['type' => 'list', 'items' => [
                    'Filter by status, or use the <b>Pending payment</b> filter to isolate cash-billed requests waiting on a verified payment slip.',
                    'Search matches the request reference and customer name.',
                ]],
                ['type' => 'sub', 'text' => 'Three independent status tracks'],
                ['type' => 'p', 'text' => 'Request, candidate and scope statuses move separately. A request can legitimately be <code>complete</code> while individual candidates within it are <code>flagged</code> — that is a normal outcome, not an inconsistency.'],
                ['type' => 'table', 'head' => ['Level', 'Allowed values'], 'widths' => ['22%', '78%'], 'rows' => [
                    ['Request', '<code>new</code> · <code>in_progress</code> · <code>rejected</code> · <code>complete</code> · <code>updated</code>'],
                    ['Candidate', '<code>new</code> · <code>in_progress</code> · <code>flagged</code> · <code>complete</code>'],
                    ['Scope', 'Per-check status set on the candidate\'s scope row, with its own findings.'],
                ]],
                ['type' => 'sub', 'text' => 'Working a request'],
                ['type' => 'steps', 'items' => [
                    'Open the request from the queue. The header shows the reference, customer, type and current status.',
                    'For cash-billed customers, verify the payment slip first — see below. Nothing else should start until payment clears.',
                    'Move the request to <code>in_progress</code> and set each candidate to <code>in_progress</code> as you pick it up.',
                    'For each scope, set its status and record findings (Section 7).',
                    'Mark candidates <code>complete</code>, or <code>flagged</code> where a record was identified.',
                    'Issue the report (Section 8). Issuing a <b>Full</b> report moves the request to <code>complete</code> automatically.',
                ]],
                ['type' => 'sub', 'text' => 'Turnaround time'],
                ['type' => 'p', 'text' => 'TAT is measured in <b>working</b> time only — Monday to Friday, 09:00 to 18:00 Asia/Kuala_Lumpur, excluding public holidays configured under Business Holidays. Evenings, weekends and holidays do not count against you.'],
                ['type' => 'note', 'title' => 'The TAT clock pauses', 'text' => 'While a request sits at status <code>rejected</code> the clock stops. Use <code>rejected</code> with a rejection reason when you are genuinely blocked waiting on the customer — it is the honest way to stop the SLA running against work you cannot do.'],
                ['type' => 'sub', 'text' => 'Verifying a payment slip (cash / per-request billing)'],
                ['type' => 'p', 'text' => 'Customers on <code>per_request</code> billing pay upfront and upload a slip. Requests stay at <code>new</code> until an admin with <code>transaction.manage</code> verifies it.'],
                ['type' => 'steps', 'items' => [
                    'Open the request and download the uploaded slip to check it against the amount due.',
                    'Select <b>Verify payment</b>.',
                    'The system records a transaction, flips the request to <code>in_progress</code>, and writes an audit entry — all in one database transaction, so it either all happens or none of it does.',
                ]],
                ['type' => 'warn', 'title' => 'The booked amount includes SST', 'text' => 'The transaction records the SST-inclusive total (subtotal plus '.$sst.'% SST), not the bare subtotal. Check the slip against the SST-inclusive figure shown on the request.'],
                ['type' => 'sub', 'text' => 'Candidate documents'],
                ['type' => 'p', 'text' => 'Documents uploaded by the customer — identity documents, resumes, signed consent forms — are listed against each candidate and stream from the client portal\'s storage. Downloading them requires <code>pdpa.documents</code>. Every download is audit-logged.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Open the Request Queue', 'route' => 'requests.index'],
                ]],
            ],
        ];
    }

    private static function findings(): array
    {
        return [
            'id' => 'findings',
            'title' => 'Recording Findings',
            'permission' => 'request.update',
            'summary' => 'The four findings editors and what each field drives in the report.',
            'blocks' => [
                ['type' => 'p', 'text' => 'Findings are recorded per scope, per candidate, and are what the screening report renders. The editor changes shape depending on the kind of scope you opened — the portal works out the kind from the scope name and category, and re-checks that decision on the server when you save.'],
                ['type' => 'table', 'head' => ['Editor', 'Used for', 'Holds'], 'widths' => ['20%', '38%', '42%'], 'rows' => [
                    ['<b>Generic</b>', 'Compliance and record checks — criminal, sanctions, PEP, bankruptcy, litigation', 'One result plus any number of identified records'],
                    ['<b>Employment</b>', 'Employment and work-history verification', 'A <b>single</b> employer with a validation grid'],
                    ['<b>Academic</b>', 'Education and qualification verification', '<b>Multiple</b> credentials — one per institution'],
                    ['<b>Referee</b>', 'Reference and referee interviews', '<b>Multiple</b> referees — one per person interviewed'],
                ]],
                ['type' => 'sub', 'text' => 'Generic scopes'],
                ['type' => 'p', 'text' => 'Set the <b>result type</b> and <b>risk level</b>; these drive the coloured result cell and the risk dot in the report. Add a record entry for every finding, with its title, act, section, offence description, penalty, verdict and per-record risk.'],
                ['type' => 'table', 'head' => ['Result type', 'Renders as'], 'widths' => ['30%', '70%'], 'rows' => [
                    ['<code>clean</code>', 'No adverse record found'],
                    ['<code>record_identified</code>', 'A record was found — details listed beneath'],
                    ['<code>adverse</code>', 'Adverse information short of a formal record'],
                    ['<code>not_requested</code>', 'Scope not run'],
                ]],
                ['type' => 'p', 'text' => 'Risk levels are <code>high</code>, <code>medium</code>, <code>low</code> and <code>nil</code>. If you leave result type or risk level blank they are inferred from the scope status — a complete scope reads as clean and low risk, a flagged scope as record identified and high risk. Set them explicitly whenever the inference would be wrong.'],
                ['type' => 'sub', 'text' => 'Employment, academic and referee scopes'],
                ['type' => 'p', 'text' => 'These use a validation grid: for each aspect you record what the candidate <b>provided</b>, what you <b>verified</b>, whether it matched, the risk, and your interpretation.'],
                ['type' => 'table', 'head' => ['Match value', 'Meaning', 'Usual risk'], 'widths' => ['24%', '48%', '28%'], 'rows' => [
                    ['<code>match</code>', 'Verified as stated', '<code>low</code>'],
                    ['<code>partial</code>', 'Minor variation, acceptable with caution', '<code>moderate</code>'],
                    ['<code>no_record</code>', 'No record available to verify against', '<code>high</code>'],
                    ['<code>discrepancy</code>', 'Verified information contradicts what was provided', '<code>high</code> or <code>critical</code>'],
                ]],
                ['type' => 'list', 'items' => [
                    '<b>Employment</b> — one employer per scope, plus the verifier you spoke to, an overall risk and a recommended action.',
                    '<b>Academic</b> — use <b>+ Add Credential</b> for a second institution. Each credential also records recognition: whether the institution is recognised by MOHE/MQA and whether the programme is accredited.',
                    '<b>Referee</b> — use <b>+ Add Referee</b> for each person interviewed. Record whether contact was established, whether they consented, whether they are independent, a credibility weight, and a rating and reply per question category.',
                ]],
                ['type' => 'note', 'title' => 'Older findings still work', 'text' => 'Findings saved before the structured editors existed continue to render exactly as before. The first time you save such a scope it is migrated to the current shape — nothing is lost.'],
                ['type' => 'warn', 'title' => 'Findings feed issued reports', 'text' => 'Changing findings after a report has been issued does not alter the issued PDF — those are immutable snapshots. To correct a report that has gone out, issue a superseding version (Section 8).'],
            ],
        ];
    }

    private static function reports(): array
    {
        return [
            'id' => 'reports',
            'title' => 'Reports & Versioning',
            'permission' => 'Preview/view/download needs report.view; issuing needs request.update',
            'summary' => 'Preview, issue, supersede, and the rules that stop duplicate versions.',
            'blocks' => [
                ['type' => 'p', 'text' => 'There are two distinct things you can do with a report, and the difference matters.'],
                ['type' => 'table', 'head' => ['Action', 'Effect'], 'widths' => ['22%', '78%'], 'rows' => [
                    ['<b>Preview</b>', 'Renders the report from the data as it stands right now. Nothing is saved, no version is created, no status changes. Available to anyone with read access.'],
                    ['<b>Generate</b>', 'Issues a permanent, numbered version. The PDF is written to disk, hashed, and recorded. It cannot be edited or deleted afterwards.'],
                ]],
                ['type' => 'sub', 'text' => 'Report types'],
                ['type' => 'p', 'text' => 'Two types can be issued from the portal. Each is versioned independently, so a request can be on Prelim v2 and Full v1 at the same time.'],
                ['type' => 'table', 'head' => ['Type', 'Purpose', 'Moves request status?'], 'widths' => ['18%', '56%', '26%'], 'rows' => [
                    ['<b>Prelim</b>', 'Interim findings issued before all scopes close', 'No'],
                    ['<b>Full</b>', 'The final deliverable', 'Yes — to <code>complete</code>, or <code>updated</code> if already complete'],
                ]],
                ['type' => 'p', 'text' => 'A third type, <b>Basic</b>, exists on historical records as a bookkeeping artefact. It is not offered by the Generate panel and, like Prelim, never moves the request status.'],
                ['type' => 'sub', 'text' => 'Issuing a report'],
                ['type' => 'steps', 'items' => [
                    'Open the request and go to <b>Generate Report</b>.',
                    'Preview first. It is the same renderer, so what you see is what will be issued.',
                    'Select <b>Prelim</b> or <b>Full</b>. Each button shows the version it will issue, and greys out when there is nothing new to issue.',
                    'The version appears in the version list with its number, issue date, the admin who issued it and a short hash.',
                ]],
                ['type' => 'sub', 'text' => 'Duplicate protection'],
                ['type' => 'p', 'text' => 'The system fingerprints the report content when you issue it. If you try to issue the same type again with <b>no data change since the last version</b>, it refuses and tells you when the last version was issued. This is deliberate — it stops the version history filling with identical PDFs.'],
                ['type' => 'p', 'text' => 'To proceed anyway you must supersede: choose the version being replaced and give a reason. Both the supersession and the new version are audit-logged.'],
                ['type' => 'note', 'title' => 'Why re-issuing a Full report is not blocked as a duplicate', 'text' => 'Issuing a Full report changes the request status, and a status change alone would otherwise look like a content change on the next click. The fingerprint deliberately ignores status, so only real findings and metadata changes count.'],
                ['type' => 'sub', 'text' => 'Report metadata'],
                ['type' => 'p', 'text' => 'The metadata panel holds the dates printed on the report. Completion dates are filled in automatically the first time you issue a Preliminary or Full report if you have not set them yourself.'],
                ['type' => 'sub', 'text' => 'Retrieving an issued version'],
                ['type' => 'p', 'text' => 'Every issued version can be viewed in the browser or downloaded, and returns the exact original bytes. Each PDF carries the first 8 characters of its own file hash in the footer, so a printed copy can be traced back to its record. Views and downloads are audit-logged.'],
                ['type' => 'warn', 'title' => 'Missing file', 'text' => 'If a version\'s PDF has been removed from disk the download returns an error rather than silently re-rendering. It will not regenerate — the point of a version is that its bytes never change.'],
            ],
        ];
    }

    private static function customers(): array
    {
        return [
            'id' => 'customers',
            'title' => 'Customers & Agreements',
            'permission' => 'Read for all roles; changes need customer.manage',
            'summary' => 'Corporate accounts, their portal users, and service contracts.',
            'blocks' => [
                ['type' => 'p', 'text' => 'A <b>customer</b> is a corporate client. Under it sit <b>customer users</b> (the people who log in to the client portal), <b>agreements</b> (the service contract), custom pricing, invoices and requests.'],
                ['type' => 'sub', 'text' => 'Creating a customer'],
                ['type' => 'steps', 'items' => [
                    'Go to <b>Customers → New customer</b>.',
                    'Fill in company details — name, registration number, address, country, industry — and the primary contact.',
                    'Save. The customer exists but nobody can log in yet.',
                    'Use <b>Provision primary user</b> to create the first client portal login. An invitation email is sent to that address.',
                    'If the invitation is missed, <b>Resend invitation</b> issues a fresh one.',
                ]],
                ['type' => 'sub', 'text' => 'Agreements'],
                ['type' => 'p', 'text' => 'An agreement records the contract type, start and expiry dates, SLA turnaround, billing mode, payment terms and any additional terms. The billing mode is the important one operationally.'],
                ['type' => 'table', 'head' => ['Billing mode', 'How it works'], 'widths' => ['26%', '74%'], 'rows' => [
                    ['<code>monthly</code>', 'Post-paid. Requests proceed immediately; work is invoiced in the monthly billing run.'],
                    ['<code>per_request</code>', 'Cash / upfront. Each request waits at <code>new</code> until its payment slip is verified.'],
                ]],
                ['type' => 'p', 'text' => 'Days remaining is calculated live from the expiry date. Agreements inside 60 days appear on the dashboard; inside 14 days treat renewal as urgent.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Open Customers', 'route' => 'customers.index'],
                ]],
            ],
        ];
    }

    private static function pricing(): array
    {
        return [
            'id' => 'pricing',
            'title' => 'Scope Pricing',
            'permission' => 'Read for all roles; changes need pricing.manage',
            'summary' => 'Per-customer prices and the price-on-request rule.',
            'blocks' => [
                ['type' => 'p', 'text' => 'Each scope type has a list price. A customer-specific price overrides it, and once set applies to everything that customer submits from then on.'],
                ['type' => 'steps', 'items' => [
                    'Open <b>Scope Pricing</b> and choose the customer.',
                    'Their scope list loads with the current effective price for each.',
                    'Edit the prices you need and save. Rows you leave alone keep falling back to the list price.',
                ]],
                ['type' => 'warn', 'title' => 'Malaysia scopes are price-on-request', 'text' => 'Malaysian scopes carry no list price — the price <b>must</b> be set per customer. Until it is, the scope shows "Price on request", the client portal refuses to accept a request containing it, and invoice generation skips the line with a warning. A request whose only lines are unpriced will not be linked to an invoice at all.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Open Scope Pricing', 'route' => 'pricing.index'],
                ]],
            ],
        ];
    }

    private static function invoices(): array
    {
        $sst = rtrim(rtrim(number_format((float) config('billing.sst_rate', 0.06) * 100, 2), '0'), '.');

        return [
            'id' => 'invoices',
            'title' => 'Invoices & Payment Receipts',
            'permission' => 'Read for all roles; invoice.manage and transaction.manage to act',
            'summary' => 'Billing runs, invoice lifecycle, and verifying customer receipts.',
            'blocks' => [
                ['type' => 'p', 'text' => 'Invoices are numbered <code>INV-YYYY-NNN</code> and carry a subtotal, '.$sst.'% SST and a total. Statuses are <code>unpaid</code>, <code>paid</code> and <code>overdue</code>.'],
                ['type' => 'sub', 'text' => 'Creating invoices'],
                ['type' => 'list', 'items' => [
                    '<b>Single invoice</b> — choose the customer and period; billable requests for that period are pulled in as line items, which you can review before saving.',
                    '<b>Bulk generate</b> — run the whole billing period across all monthly-billed customers. Always use <b>Preview</b> first: it shows exactly which customers and line items will be produced, including any lines skipped for missing prices. Only then confirm.',
                ]],
                ['type' => 'sub', 'text' => 'Lifecycle'],
                ['type' => 'steps', 'items' => [
                    'Invoice is created as <code>unpaid</code> with an issue date and due date.',
                    'If it is still unpaid after its due date, a scheduled job at 07:30 MYT flips it to <code>overdue</code>.',
                    'It becomes <code>paid</code> either when verified receipts cover the total, or when an admin marks it paid manually.',
                ]],
                ['type' => 'sub', 'text' => 'Verifying a payment receipt'],
                ['type' => 'p', 'text' => 'Customers upload receipts against an invoice from the client portal. They arrive as <code>pending</code>.'],
                ['type' => 'steps', 'items' => [
                    'Open the receipt and view the uploaded file.',
                    'Check the claimed amount, payment date and reference against the invoice.',
                    'Select <b>Verify</b>, or <b>Reject</b> with a note explaining what is wrong so the customer can correct it.',
                ]],
                ['type' => 'p', 'text' => 'Verifying runs a cascade in a single database transaction: the receipt is marked verified, a transaction is recorded, and if verified receipts now cover the invoice total the invoice flips to <code>paid</code>. Any linked requests still sitting at <code>new</code> are released to <code>in_progress</code>. The whole cascade is audit-logged.'],
                ['type' => 'note', 'title' => 'Receipts with no claimed amount', 'text' => 'If a receipt arrives without a claimed amount, verifying it treats the payment as settling the outstanding balance and writes that figure onto the receipt. This is what allows it to count towards coverage — without it the invoice would never reach <code>paid</code> and gated requests would stay stuck at <code>new</code>.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Open Invoices', 'route' => 'invoices.index'],
                ]],
            ],
        ];
    }

    private static function transactions(): array
    {
        return [
            'id' => 'transactions',
            'title' => 'Transactions',
            'permission' => 'Read for all roles; recording needs transaction.manage',
            'summary' => 'The money ledger and when to add an entry by hand.',
            'blocks' => [
                ['type' => 'p', 'text' => 'The transactions ledger records money movement against a customer. Most entries are written automatically when you verify a payment slip or receipt — record one by hand only for movements that have no slip behind them.'],
                ['type' => 'table', 'head' => ['Type', 'Use for'], 'widths' => ['26%', '74%'], 'rows' => [
                    ['<code>topup</code>', 'Funds added to the customer\'s balance in advance of work.'],
                    ['<code>payment</code>', 'Settlement of an invoice or a cash-billed request.'],
                    ['<code>adjustment</code>', 'Corrections, credits and write-offs. Always put the reason in the notes.'],
                ]],
                ['type' => 'warn', 'title' => 'Manual entries do not cascade', 'text' => 'Recording a transaction by hand does not mark any invoice paid and does not release any request. If a customer has actually paid an invoice, verify their receipt or use <b>Mark paid</b> on the invoice instead — those paths run the full cascade.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Open Transactions', 'route' => 'transactions.index'],
                ]],
            ],
        ];
    }

    private static function configuration(): array
    {
        return [
            'id' => 'configuration',
            'title' => 'Configuration',
            'permission' => 'config.scopes / config.countries',
            'summary' => 'Scope types, countries and the holiday calendar behind TAT.',
            'blocks' => [
                ['type' => 'sub', 'text' => 'Scope types'],
                ['type' => 'p', 'text' => 'Scope types are the individual checks offered. Each belongs to a country and carries a name, category, turnaround, price, description and whether signed consent is required.'],
                ['type' => 'list', 'items' => [
                    '<b>Name and description matter.</b> Both are printed on the screening report, and the name is what determines which findings editor an analyst gets.',
                    '<b>Price on request</b> — tick this for scopes with no list price (all Malaysian scopes). Price must then be set per customer under Scope Pricing.',
                    '<b>Requires signed consent</b> — forces the client portal to collect a signed consent form before the request can be submitted.',
                    '<b>Sort order</b> drives the order everywhere: configuration, pricing, the request form and the PDF report.',
                ]],
                ['type' => 'warn', 'title' => 'Renaming and removing scopes', 'text' => 'Scopes are referenced by requests already in flight and by issued reports. Renaming changes what appears on future reports; removing one is disruptive to any request using it. Treat both as changes to coordinate, not routine edits.'],
                ['type' => 'sub', 'text' => 'Countries'],
                ['type' => 'p', 'text' => 'Countries carry a name, 3-character code, flag, region and currency. Each scope type belongs to exactly one country, so adding a country is the first step in offering a new market.'],
                ['type' => 'sub', 'text' => 'Business holidays'],
                ['type' => 'p', 'text' => 'The holiday calendar feeds the TAT calculation directly: a day listed here does not count as working time. Working hours are Monday to Friday, 09:00 to 18:00, Asia/Kuala_Lumpur.'],
                ['type' => 'list', 'items' => [
                    'Add holidays individually, or use <b>Sync</b> to pull the official public holiday list.',
                    'A scheduled job refreshes the calendar annually, but check it at the start of each year.',
                    'Changes take effect on the next TAT calculation — there is nothing else to do after saving.',
                ]],
                ['type' => 'links', 'items' => [
                    ['label' => 'Scope Types', 'route' => 'config.scopes.index'],
                    ['label' => 'Countries', 'route' => 'config.countries.index'],
                    ['label' => 'Business Holidays', 'route' => 'config.holidays.index'],
                ]],
            ],
        ];
    }

    private static function compliance(): array
    {
        return [
            'id' => 'compliance',
            'title' => 'PDPA & Compliance',
            'permission' => 'pdpa.consent / pdpa.dsar / pdpa.retention',
            'summary' => 'Consent records, Data Subject Requests, retention and erasure.',
            'blocks' => [
                ['type' => 'sub', 'text' => 'Consent records'],
                ['type' => 'p', 'text' => 'Consent is recorded against a candidate, with the evidence file attached. Where a scope requires signed consent, the customer captures it in the client portal; you can record it here and download the evidence when needed. Every evidence download is audit-logged.'],
                ['type' => 'sub', 'text' => 'Data Subject Requests'],
                ['type' => 'p', 'text' => 'A DSAR is a request from an individual about their own data. Five types are supported:'],
                ['type' => 'table', 'head' => ['Type', 'The individual is asking to'], 'widths' => ['28%', '72%'], 'rows' => [
                    ['<code>access</code>', 'Receive a copy of the data held about them'],
                    ['<code>erasure</code>', 'Have their data deleted — the right to be forgotten'],
                    ['<code>rectification</code>', 'Have inaccurate data corrected'],
                    ['<code>portability</code>', 'Receive their data in a machine-readable form'],
                    ['<code>cease_processing</code>', 'Have processing of their data stopped'],
                ]],
                ['type' => 'p', 'text' => 'The lifecycle is strictly ordered and the portal enforces it — you cannot skip a step.'],
                ['type' => 'steps', 'items' => [
                    '<code>received</code> — the request has been logged. Record the requester, their relationship to the data subject, and the type.',
                    '<code>verifying_identity</code> — you have started confirming the requester is who they claim to be. Only a request in <code>received</code> can be moved here.',
                    '<code>in_progress</code> — identity confirmed. Link the request to the candidate record it concerns.',
                    '<code>completed</code> or <code>rejected</code> — close it out. Rejection requires a reason.',
                ]],
                ['type' => 'p', 'text' => 'The index shows counts by stage and highlights requests approaching their statutory deadline.'],
                ['type' => 'sub', 'text' => 'Executing an erasure'],
                ['type' => 'warn', 'title' => 'This permanently destroys data', 'text' => 'Erasure masks the candidate\'s personal fields, clears the search index that makes their identity number findable, and <b>deletes their uploaded documents and consent evidence files from disk</b>. It cannot be undone. Issued report PDFs and payment records are deliberately out of scope — reports are immutable and financial records are held under separate retention rules.'],
                ['type' => 'p', 'text' => 'The portal will only run it when the DSAR is type <code>erasure</code> and status <code>in_progress</code> — meaning identity has been verified — and it requires an explicit confirmation. What was deleted is recorded in the audit log.'],
                ['type' => 'sub', 'text' => 'Data retention'],
                ['type' => 'p', 'text' => 'Retention policies set how long each type of record is kept before automatic purging. Retention is configured in days, from a minimum of 30. A scheduled purge runs daily at 02:30 MYT; <b>Purge now</b> runs the same job immediately.'],
                ['type' => 'warn', 'title' => 'Purge is the same destructive operation as erasure', 'text' => 'Purging applies the same redaction to every candidate past the retention window. Shortening a retention period can therefore delete a large number of records on the next run. Change these values deliberately.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Data Subject Requests', 'route' => 'compliance.dsar.index'],
                    ['label' => 'Data Retention', 'route' => 'compliance.retention.index'],
                ]],
            ],
        ];
    }

    private static function staff(): array
    {
        return [
            'id' => 'staff',
            'title' => 'Staff Administration',
            'permission' => 'staff.manage',
            'summary' => 'Admin accounts, per-user permissions and 2FA resets.',
            'blocks' => [
                ['type' => 'steps', 'items' => [
                    '<b>Add staff</b> — create the account with a name, work email, role and initial password. They set up their own two-factor on first sign-in.',
                    '<b>Activate / deactivate</b> — deactivating blocks sign-in immediately while preserving the account and its audit history. This is the correct way to handle someone leaving.',
                    '<b>Permissions</b> — override individual permissions for one person on top of their role. Each permission can be explicitly granted or explicitly revoked.',
                    '<b>Reset 2FA</b> — clears their authenticator binding so they can enrol a new device. Use when someone loses their phone.',
                ]],
                ['type' => 'note', 'title' => 'Overrides beat the role', 'text' => 'A per-user override wins over the role default in both directions — it can grant a permission the role lacks, or take away one the role has. If someone\'s access looks wrong, check their overrides before changing the role matrix for everyone.'],
                ['type' => 'warn', 'title' => 'super_admin bypasses everything', 'text' => 'A super_admin passes every permission check, including permissions added in future. Grant the role sparingly and prefer targeted permissions.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Staff', 'route' => 'staff.index'],
                    ['label' => 'Roles & Permissions matrix', 'route' => 'permissions.index'],
                ]],
            ],
        ];
    }

    private static function auditLog(): array
    {
        return [
            'id' => 'audit',
            'title' => 'Audit Log',
            'permission' => 'staff.manage',
            'summary' => 'The append-only trail of who did what.',
            'blocks' => [
                ['type' => 'p', 'text' => 'The audit log is append-only — entries cannot be edited or deleted by anyone, including a super_admin. It captures two kinds of event:'],
                ['type' => 'list', 'items' => [
                    '<b>Record changes</b> — creates, updates and deletes across the system, with the before and after values.',
                    '<b>Workflow events</b> — report issued, report superseded, report viewed or downloaded, payment verified, erasure executed, document downloaded.',
                ]],
                ['type' => 'note', 'title' => 'Secrets are never written to the log', 'text' => 'Passwords, two-factor secrets and recovery codes are scrubbed before an entry is stored, so the log is safe to review and export.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Open the Audit Log', 'route' => 'audit.index'],
                ]],
            ],
        ];
    }

    private static function account(): array
    {
        return [
            'id' => 'account',
            'title' => 'My Account',
            'permission' => null,
            'summary' => 'Your own profile and security settings.',
            'blocks' => [
                ['type' => 'list', 'items' => [
                    '<b>Profile</b> — update your name, email and avatar. Removing your avatar falls back to your initials.',
                    '<b>Security</b> — enable or disable two-factor authentication and regenerate recovery codes.',
                ]],
                ['type' => 'warn', 'title' => 'Think before disabling two-factor', 'text' => 'You hold access to candidate personal data, financial records and the erasure tools. Keep two-factor on unless you have a specific reason not to, and re-enable it as soon as that reason passes.'],
                ['type' => 'links', 'items' => [
                    ['label' => 'Profile', 'route' => 'account.profile'],
                    ['label' => 'Security', 'route' => 'account.security'],
                ]],
            ],
        ];
    }

    private static function reference(): array
    {
        $sst = rtrim(rtrim(number_format((float) config('billing.sst_rate', 0.06) * 100, 2), '0'), '.');

        return [
            'id' => 'reference',
            'title' => 'Reference Tables',
            'permission' => null,
            'summary' => 'Status values, formats and terminology — the desk-copy section.',
            'blocks' => [
                ['type' => 'sub', 'text' => 'Status values'],
                ['type' => 'table', 'head' => ['Where', 'Allowed values'], 'widths' => ['34%', '66%'], 'rows' => [
                    ['Screening request', '<code>new</code> · <code>in_progress</code> · <code>rejected</code> · <code>complete</code> · <code>updated</code>'],
                    ['Candidate', '<code>new</code> · <code>in_progress</code> · <code>flagged</code> · <code>complete</code>'],
                    ['Invoice', '<code>unpaid</code> · <code>paid</code> · <code>overdue</code>'],
                    ['Payment receipt', '<code>pending</code> · <code>verified</code> · <code>rejected</code>'],
                    ['Transaction type', '<code>topup</code> · <code>payment</code> · <code>adjustment</code>'],
                    ['DSAR', '<code>received</code> · <code>verifying_identity</code> · <code>in_progress</code> · <code>completed</code> · <code>rejected</code>'],
                    ['Admin role', '<code>super_admin</code> · <code>operations</code> · <code>finance</code> · <code>viewer</code>'],
                    ['Admin status', '<code>active</code> · <code>inactive</code>'],
                ]],
                ['type' => 'sub', 'text' => 'Formats and constants'],
                ['type' => 'table', 'head' => ['Item', 'Value'], 'widths' => ['34%', '66%'], 'rows' => [
                    ['Request reference', '<code>REP-YYYY-NNNN</code>'],
                    ['Invoice number', '<code>INV-YYYY-NNN</code>'],
                    ['Tax', $sst.'% SST on invoice subtotals and cash request totals'],
                    ['Working hours', 'Mon–Fri, 09:00–18:00, Asia/Kuala_Lumpur'],
                    ['Agreement warning', '60 days to expiry (warning), 14 days (critical)'],
                ]],
                ['type' => 'sub', 'text' => 'Scheduled jobs'],
                ['type' => 'table', 'head' => ['Time (MYT)', 'Job'], 'widths' => ['22%', '78%'], 'rows' => [
                    ['02:30 daily', 'PDPA retention purge'],
                    ['07:30 daily', 'Flip unpaid invoices past their due date to <code>overdue</code>'],
                    ['08:00 daily', 'Notifications'],
                    ['Annually', 'Public holiday calendar sync'],
                ]],
                ['type' => 'sub', 'text' => 'Terminology'],
                ['type' => 'table', 'head' => ['Term', 'Means'], 'widths' => ['26%', '74%'], 'rows' => [
                    ['<b>Request</b>', 'One submission from a customer, containing one or more candidates.'],
                    ['<b>Candidate</b>', 'One person being screened within a request.'],
                    ['<b>Scope</b>', 'One individual check run against one candidate.'],
                    ['<b>Findings</b>', 'What a scope turned up — the content of the report.'],
                    ['<b>Version</b>', 'A permanently issued report PDF. Immutable once created.'],
                    ['<b>Supersede</b>', 'Issue a corrected version that formally replaces an earlier one.'],
                    ['<b>TAT</b>', 'Turnaround time, counted in working hours only.'],
                    ['<b>DSAR</b>', 'Data Subject Request — an individual exercising their rights over their own data.'],
                    ['<b>Redaction</b>', 'Permanent masking of personal data plus deletion of the associated files.'],
                ]],
            ],
        ];
    }

    private static function troubleshooting(): array
    {
        return [
            'id' => 'troubleshooting',
            'title' => 'Troubleshooting',
            'permission' => null,
            'summary' => 'The things that most often look broken but are not.',
            'blocks' => [
                ['type' => 'table', 'head' => ['What you see', 'What is happening'], 'widths' => ['38%', '62%'], 'rows' => [
                    ['A sidebar entry is missing', 'You do not hold that permission. Ask someone with <code>staff.manage</code>.'],
                    ['403 when opening a URL directly', 'Same cause — the route is permission-gated.'],
                    ['"No changes since…" on Generate', 'Nothing in the report content has changed since the last version of that type. Edit findings or metadata, or supersede with a reason.'],
                    ['A scope shows "Price on request"', 'No customer-specific price is set. Set one under Scope Pricing — until you do, the scope cannot be invoiced and the client portal will not accept it.'],
                    ['Invoice will not flip to paid', 'Verified receipts do not yet cover the total. Check the claimed amounts on each verified receipt against the invoice total.'],
                    ['A request is stuck at <code>new</code>', 'It is cash-billed and the payment slip has not been verified. Verifying it releases the request.'],
                    ['TAT looks frozen', 'The request is at <code>rejected</code>, which pauses the clock by design.'],
                    ['No Preview / View / Download report buttons', 'Report PDFs require <code>report.view</code>, which is separate from read access.'],
                    ['Cannot download a candidate document', 'Downloading requires <code>pdpa.documents</code>, which is separate from read access.'],
                    ['A report download returns an error', 'The stored PDF is missing from disk. Issued versions are never re-rendered — raise it with the project owner.'],
                    ['Cannot execute an erasure', 'The DSAR must be type <code>erasure</code> and at status <code>in_progress</code>, meaning identity has been verified.'],
                ]],
                ['type' => 'note', 'title' => 'Still stuck?', 'text' => 'The audit log records what actually happened, including the before and after values of any change. It is usually faster than guessing.'],
            ],
        ];
    }
}
