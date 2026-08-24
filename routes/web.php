<?php

use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\AgreementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\CandidateDocumentController;
use App\Http\Controllers\Compliance\ConsentController;
use App\Http\Controllers\Compliance\DataSubjectRequestController;
use App\Http\Controllers\Compliance\RetentionController;
use App\Http\Controllers\Config\BusinessHolidayController;
use App\Http\Controllers\Config\CountryController;
use App\Http\Controllers\Config\ScopeTypeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequestQueueController;
use App\Http\Controllers\ScopePricingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// ─── Auth ────────────────────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// 2FA challenge — accessible only when password verified, before full login completes.
Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])->name('two-factor.verify')->middleware('throttle:5,1');

// ─── Protected admin routes ───────────────────────────────────────────────────
Route::middleware('admin.auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));

    // Dashboard — any authenticated admin
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Account / profile + security — every admin manages their own
    Route::get('/account', fn () => redirect()->route('account.profile'));
    Route::get('/account/profile', [ProfileController::class, 'show'])->name('account.profile');
    Route::put('/account/profile', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::delete('/account/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('account.profile.avatar.remove');

    Route::get('/account/security', [SecurityController::class, 'show'])->name('account.security');
    Route::post('/account/security/two-factor', [SecurityController::class, 'enable'])->name('account.two-factor.enable');
    Route::post('/account/security/two-factor/confirm', [SecurityController::class, 'confirm'])->name('account.two-factor.confirm');
    Route::delete('/account/security/two-factor/setup', [SecurityController::class, 'cancelSetup'])->name('account.two-factor.cancel');
    Route::delete('/account/security/two-factor', [SecurityController::class, 'disable'])->name('account.two-factor.disable');
    Route::post('/account/security/two-factor/recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('account.two-factor.regenerate-codes');

    // ── Read-only routes (any admin role, including viewer) ──────────────────
    Route::get('/requests', [RequestQueueController::class, 'index'])->name('requests.index');
    Route::get('/requests/{screeningRequest}', [RequestQueueController::class, 'show'])->name('requests.show');
    // Live preview — never persists. Anyone with read access can see it.
    Route::get('/requests/{screeningRequest}/report/preview', [ReportController::class, 'preview'])
        ->name('requests.report.preview');

    // Re-download a persisted version's exact bytes.
    Route::get('/requests/{screeningRequest}/report/versions/{version}/download', [ReportController::class, 'download'])
        ->name('requests.report.download');

    Route::get('/requests/{screeningRequest}/report/versions/{version}/view', [ReportController::class, 'view'])
        ->name('requests.report.view');

    // Customer-uploaded candidate documents (NRIC, resume, consent…) — served via client_local.
    Route::get('/requests/{screeningRequest}/candidates/{candidateId}/documents/{documentId}', [CandidateDocumentController::class, 'download'])
        ->name('requests.candidates.documents.download');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

    Route::get('/pricing', [ScopePricingController::class, 'index'])->name('pricing.index');
    Route::get('/pricing/{customer}/scopes', [ScopePricingController::class, 'scopesJson'])->name('pricing.scopes-json');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // NOTE: the bare `/customers/{customer}` and `/invoices/{invoice}` show routes
    // are declared at the BOTTOM of this group — see "Catch-all show routes".

    // ── request.update ───────────────────────────────────────────────────────
    Route::middleware('admin.can:request.update')->group(function () {
        Route::patch('/requests/{screeningRequest}/status', [RequestQueueController::class, 'updateStatus'])->name('requests.status');
        Route::patch('/requests/{screeningRequest}/candidates/{candidateId}/status', [RequestQueueController::class, 'updateCandidateStatus'])->name('requests.candidates.status');
        Route::patch('/requests/{screeningRequest}/candidates/{candidateId}/scopes/{scopeTypeId}/status', [RequestQueueController::class, 'updateScopeStatus'])->name('requests.scope.status');
        Route::patch('/requests/{screeningRequest}/candidates/{candidateId}/scopes/{scopeTypeId}/findings', [RequestQueueController::class, 'updateScopeFindings'])->name('requests.scope.findings');
        Route::patch('/requests/{screeningRequest}/meta', [RequestQueueController::class, 'updateMeta'])->name('requests.meta');

        // Issue a new persisted version (Basic / Prelim / Full).
        Route::post('/requests/{screeningRequest}/report/generate', [ReportController::class, 'generate'])
            ->name('requests.report.generate');
    });

    // ── customer.manage ──────────────────────────────────────────────────────
    Route::middleware('admin.can:customer.manage')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

        // {user} is a CustomerUser — it has no HasHashid trait, so its route key
        // really is the integer id and whereNumber is correct here.
        Route::post('/customers/{customer}/users/{user}/resend-invitation', [CustomerController::class, 'resendInvitation'])
            ->whereNumber('user')
            ->name('customers.users.resend-invitation');

        Route::post('/customers/{customer}/provision-primary-user', [CustomerController::class, 'provisionPrimaryUser'])
            ->name('customers.provision-primary-user');

        Route::get('/customers/{customer}/agreements/create', [AgreementController::class, 'create'])->name('customers.agreements.create');
        Route::post('/customers/{customer}/agreements', [AgreementController::class, 'store'])->name('customers.agreements.store');
        Route::get('/customers/{customer}/agreements/{agreement}/edit', [AgreementController::class, 'edit'])->name('customers.agreements.edit');
        Route::put('/customers/{customer}/agreements/{agreement}', [AgreementController::class, 'update'])->name('customers.agreements.update');
    });

    // ── pricing.manage ───────────────────────────────────────────────────────
    Route::middleware('admin.can:pricing.manage')->group(function () {
        Route::post('/pricing/{customer}', [ScopePricingController::class, 'upsert'])->name('pricing.upsert');
        Route::patch('/pricing/{customer}/scope/{scopeType}', [ScopePricingController::class, 'updateOne'])->name('pricing.update-one');
    });

    // ── invoice.manage ───────────────────────────────────────────────────────
    Route::middleware('admin.can:invoice.manage')->group(function () {
        Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::get('/invoices/bulk-generate', [InvoiceController::class, 'bulkGenerate'])->name('invoices.bulk-generate');
        Route::get('/invoices/bulk-preview', [InvoiceController::class, 'bulkPreview'])->name('invoices.bulk-preview');
        Route::post('/invoices/bulk-store', [InvoiceController::class, 'bulkStore'])->name('invoices.bulk-store');
        Route::get('/invoices/preview-items', [InvoiceController::class, 'previewItems'])->name('invoices.preview-items');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::patch('/invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.paid');
    });

    // ── transaction.manage ───────────────────────────────────────────────────
    Route::middleware('admin.can:transaction.manage')->group(function () {
        Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');

        // Verify a cash-billed customer's uploaded payment slip. Requires a slip
        // to be present. Records a transactions row, flips request status to
        // in_progress, and writes an audit entry — all in one DB transaction.
        Route::post('/requests/{screeningRequest}/verify-payment', [RequestQueueController::class, 'verifyPaymentSlip'])
            ->name('requests.verify-payment');

        // Stream the customer-uploaded payment slip file privately to admins.
        Route::get('/requests/{screeningRequest}/payment-slip', [RequestQueueController::class, 'downloadPaymentSlip'])
            ->name('requests.payment-slip.download');

        // Customer-uploaded payment receipts — admin verify/reject + view file.
        Route::post('/payment-receipts/{receipt}/verify', [PaymentReceiptController::class, 'verify'])
            ->name('payment-receipts.verify');
        Route::post('/payment-receipts/{receipt}/reject', [PaymentReceiptController::class, 'reject'])
            ->name('payment-receipts.reject');
        Route::get('/payment-receipts/{receipt}/file', [PaymentReceiptController::class, 'downloadFile'])
            ->name('payment-receipts.file');
    });

    // ── config.scopes ────────────────────────────────────────────────────────
    Route::middleware('admin.can:config.scopes')->group(function () {
        Route::get('/config/scopes', [ScopeTypeController::class, 'index'])->name('config.scopes.index');
        Route::get('/config/scopes/create', [ScopeTypeController::class, 'create'])->name('config.scopes.create');
        Route::post('/config/scopes', [ScopeTypeController::class, 'store'])->name('config.scopes.store');
        Route::get('/config/scopes/{scope}/edit', [ScopeTypeController::class, 'edit'])->name('config.scopes.edit');
        Route::put('/config/scopes/{scope}', [ScopeTypeController::class, 'update'])->name('config.scopes.update');

        // Business holidays (affect SLA/TAT calc, gated under same permission for now).
        Route::get('/config/holidays', [BusinessHolidayController::class, 'index'])->name('config.holidays.index');
        Route::post('/config/holidays', [BusinessHolidayController::class, 'store'])->name('config.holidays.store');
        Route::delete('/config/holidays/{holiday}', [BusinessHolidayController::class, 'destroy'])->name('config.holidays.destroy');
        Route::post('/config/holidays/sync', [BusinessHolidayController::class, 'syncFromApi'])->name('config.holidays.sync');
    });

    // ── config.countries ─────────────────────────────────────────────────────
    Route::middleware('admin.can:config.countries')->group(function () {
        Route::get('/config/countries', [CountryController::class, 'index'])->name('config.countries.index');
        Route::post('/config/countries', [CountryController::class, 'store'])->name('config.countries.store');
        Route::put('/config/countries/{country}', [CountryController::class, 'update'])->name('config.countries.update');
    });

    // ── staff.manage ─────────────────────────────────────────────────────────
    Route::middleware('admin.can:staff.manage')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::patch('/staff/{admin}/toggle', [StaffController::class, 'toggleStatus'])->name('staff.toggle');
        Route::get('/staff/{admin}/permissions', [StaffController::class, 'permissions'])->name('staff.permissions');
        Route::put('/staff/{admin}/permissions', [StaffController::class, 'updatePermissions'])->name('staff.permissions.update');
        Route::patch('/staff/{admin}/reset-2fa', [StaffController::class, 'resetTwoFactor'])->name('staff.reset-2fa');
    });

    // ── pdpa.consent — record candidate consent ──────────────────────────────
    Route::middleware('admin.can:pdpa.consent')->group(function () {
        Route::post('/requests/{screeningRequest}/candidates/{candidateId}/consent', [ConsentController::class, 'store'])
            ->whereNumber('candidateId')
            ->name('compliance.consent.store');
        Route::get('/compliance/consent/{consent}/evidence', [ConsentController::class, 'downloadEvidence'])
            ->name('compliance.consent.evidence');
    });

    // ── pdpa.retention — retention config + manual purge ─────────────────────
    Route::middleware('admin.can:pdpa.retention')->group(function () {
        Route::get('/compliance/retention', [RetentionController::class, 'index'])->name('compliance.retention.index');
        Route::put('/compliance/retention', [RetentionController::class, 'update'])->name('compliance.retention.update');
        Route::post('/compliance/retention/purge-now', [RetentionController::class, 'purgeNow'])->name('compliance.retention.purge-now');
    });

    // ── pdpa.dsar — Data Subject Requests ────────────────────────────────────
    Route::middleware('admin.can:pdpa.dsar')->group(function () {
        Route::get('/compliance/dsar', [DataSubjectRequestController::class, 'index'])->name('compliance.dsar.index');
        Route::get('/compliance/dsar/create', [DataSubjectRequestController::class, 'create'])->name('compliance.dsar.create');
        Route::post('/compliance/dsar', [DataSubjectRequestController::class, 'store'])->name('compliance.dsar.store');
        Route::get('/compliance/dsar/{dsar}', [DataSubjectRequestController::class, 'show'])->name('compliance.dsar.show');
        Route::patch('/compliance/dsar/{dsar}/verify', [DataSubjectRequestController::class, 'verify'])->name('compliance.dsar.verify');
        Route::patch('/compliance/dsar/{dsar}/confirm-identity', [DataSubjectRequestController::class, 'confirmIdentity'])->name('compliance.dsar.confirm-identity');
        Route::patch('/compliance/dsar/{dsar}/link-candidate', [DataSubjectRequestController::class, 'linkCandidate'])->name('compliance.dsar.link-candidate');
        Route::patch('/compliance/dsar/{dsar}/complete', [DataSubjectRequestController::class, 'complete'])->name('compliance.dsar.complete');
        Route::patch('/compliance/dsar/{dsar}/reject', [DataSubjectRequestController::class, 'reject'])->name('compliance.dsar.reject');
        Route::post('/compliance/dsar/{dsar}/execute-erasure', [DataSubjectRequestController::class, 'executeErasure'])->name('compliance.dsar.execute-erasure');
        Route::get('/compliance/dsar/{dsar}/evidence', [DataSubjectRequestController::class, 'downloadEvidence'])->name('compliance.dsar.evidence');
    });

    // ── permissions.manage (role matrix) ─────────────────────────────────────
    Route::middleware('admin.can:permissions.manage')->group(function () {
        Route::get('/permissions', [PermissionsController::class, 'index'])->name('permissions.index');
        Route::put('/permissions', [PermissionsController::class, 'update'])->name('permissions.update');
    });

    // ── audit log (super admin only via staff.manage; sensitive trail) ───────
    Route::middleware('admin.can:staff.manage')->group(function () {
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });

    // ── Catch-all show routes (read-only, any admin role) ────────────────────
    // These MUST stay last. `{customer}`/`{invoice}` are hashid-bound, and a
    // hashid is indistinguishable from a literal word like "create" — so a bare
    // `/customers/{customer}` declared earlier would swallow `/customers/create`,
    // `/invoices/bulk-generate`, etc. and 404 when the hashid decode fails.
    // Declaring them after every literal-segment route lets the literals win.
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
});
