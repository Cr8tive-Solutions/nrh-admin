<?php

use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\AgreementController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Config\CountryController;
use App\Http\Controllers\Config\ScopeTypeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\RequestQueueController;
use App\Http\Controllers\ScopePricingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// ─── Auth ────────────────────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// 2FA challenge — accessible only when password verified, before full login completes.
Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])->name('two-factor.verify');

// ─── Protected admin routes ───────────────────────────────────────────────────
Route::middleware('admin.auth')->group(function () {

    Route::get('/', fn () => redirect()->route('dashboard'));

    // Dashboard — any authenticated admin
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Account / profile + security — every admin manages their own
    Route::get('/account', fn () => redirect()->route('account.profile'));
    Route::get('/account/profile', [\App\Http\Controllers\Account\ProfileController::class, 'show'])->name('account.profile');
    Route::put('/account/profile', [\App\Http\Controllers\Account\ProfileController::class, 'update'])->name('account.profile.update');
    Route::delete('/account/profile/avatar', [\App\Http\Controllers\Account\ProfileController::class, 'removeAvatar'])->name('account.profile.avatar.remove');

    Route::get('/account/security', [SecurityController::class, 'show'])->name('account.security');
    Route::post('/account/security/two-factor', [SecurityController::class, 'enable'])->name('account.two-factor.enable');
    Route::post('/account/security/two-factor/confirm', [SecurityController::class, 'confirm'])->name('account.two-factor.confirm');
    Route::delete('/account/security/two-factor/setup', [SecurityController::class, 'cancelSetup'])->name('account.two-factor.cancel');
    Route::delete('/account/security/two-factor', [SecurityController::class, 'disable'])->name('account.two-factor.disable');
    Route::post('/account/security/two-factor/recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('account.two-factor.regenerate-codes');

    // ── Read-only routes (any admin role, including viewer) ──────────────────
    Route::get('/requests', [RequestQueueController::class, 'index'])->name('requests.index');
    Route::get('/requests/{screeningRequest}', [RequestQueueController::class, 'show'])->name('requests.show');

    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('customers.show');

    Route::get('/pricing', [ScopePricingController::class, 'index'])->name('pricing.index');
    Route::get('/pricing/{customer}/scopes', [ScopePricingController::class, 'scopesJson'])->name('pricing.scopes-json');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->whereNumber('invoice')->name('invoices.show');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // ── request.update ───────────────────────────────────────────────────────
    Route::middleware('admin.can:request.update')->group(function () {
        Route::patch('/requests/{screeningRequest}/status', [RequestQueueController::class, 'updateStatus'])->name('requests.status');
        Route::patch('/requests/{screeningRequest}/candidates/{candidateId}/status', [RequestQueueController::class, 'updateCandidateStatus'])->name('requests.candidates.status');
        Route::patch('/requests/{screeningRequest}/candidates/{candidateId}/scopes/{scopeTypeId}/status', [RequestQueueController::class, 'updateScopeStatus'])->name('requests.scope.status');
    });

    // ── customer.manage ──────────────────────────────────────────────────────
    Route::middleware('admin.can:customer.manage')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->whereNumber('customer')->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->whereNumber('customer')->name('customers.update');

        Route::post('/customers/{customer}/users/{user}/resend-invitation', [CustomerController::class, 'resendInvitation'])
            ->whereNumber('customer')->whereNumber('user')
            ->name('customers.users.resend-invitation');

        Route::post('/customers/{customer}/provision-primary-user', [CustomerController::class, 'provisionPrimaryUser'])
            ->whereNumber('customer')
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
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::patch('/invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->whereNumber('invoice')->name('invoices.paid');
    });

    // ── transaction.manage ───────────────────────────────────────────────────
    Route::middleware('admin.can:transaction.manage')->group(function () {
        Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    });

    // ── config.scopes ────────────────────────────────────────────────────────
    Route::middleware('admin.can:config.scopes')->group(function () {
        Route::get('/config/scopes', [ScopeTypeController::class, 'index'])->name('config.scopes.index');
        Route::get('/config/scopes/create', [ScopeTypeController::class, 'create'])->name('config.scopes.create');
        Route::post('/config/scopes', [ScopeTypeController::class, 'store'])->name('config.scopes.store');
        Route::get('/config/scopes/{scope}/edit', [ScopeTypeController::class, 'edit'])->name('config.scopes.edit');
        Route::put('/config/scopes/{scope}', [ScopeTypeController::class, 'update'])->name('config.scopes.update');

        // Business holidays (affect SLA/TAT calc, gated under same permission for now).
        Route::get('/config/holidays', [\App\Http\Controllers\Config\BusinessHolidayController::class, 'index'])->name('config.holidays.index');
        Route::post('/config/holidays', [\App\Http\Controllers\Config\BusinessHolidayController::class, 'store'])->name('config.holidays.store');
        Route::delete('/config/holidays/{holiday}', [\App\Http\Controllers\Config\BusinessHolidayController::class, 'destroy'])->name('config.holidays.destroy');
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

    // ── permissions.manage (role matrix) ─────────────────────────────────────
    Route::middleware('admin.can:permissions.manage')->group(function () {
        Route::get('/permissions', [PermissionsController::class, 'index'])->name('permissions.index');
        Route::put('/permissions', [PermissionsController::class, 'update'])->name('permissions.update');
    });

    // ── audit log (super admin only via staff.manage; sensitive trail) ───────
    Route::middleware('admin.can:staff.manage')->group(function () {
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
