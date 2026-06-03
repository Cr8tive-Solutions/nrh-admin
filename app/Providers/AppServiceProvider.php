<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\Agreement;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerScopePrice;
use App\Models\CustomerUser;
use App\Models\CustomerUserInvitation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePaymentReceipt;
use App\Models\Permission;
use App\Models\RequestCandidate;
use App\Models\ScopeType;
use App\Models\ScreeningRequest;
use App\Models\Transaction;
use App\Observers\AuditObserver;
use App\Observers\PaymentReceiptNotificationObserver;
use App\Observers\ScreeningRequestNotificationObserver;
use Hashids\Hashids;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('hashids', fn () => new Hashids(config('app.key'), 8));
    }

    public function boot(): void
    {
        // @allowed('invoice.manage') ... @endallowed — RBAC/UBAC permission check.
        Blade::if('allowed', function (string $key) {
            return admin_can($key);
        });

        // Audit-log every create/update/delete on these models.
        $audited = [
            Customer::class,
            CustomerUser::class,
            CustomerUserInvitation::class,
            Agreement::class,
            Invoice::class,
            InvoiceItem::class,
            Transaction::class,
            ScreeningRequest::class,
            RequestCandidate::class,
            ScopeType::class,
            Country::class,
            Permission::class,
            CustomerScopePrice::class,
            Admin::class,
        ];

        foreach ($audited as $modelClass) {
            $modelClass::observe(AuditObserver::class);
        }

        // Notification observers
        ScreeningRequest::observe(ScreeningRequestNotificationObserver::class);
        InvoicePaymentReceipt::observe(PaymentReceiptNotificationObserver::class);

        // Share unread notification count with the admin layout on every request
        View::composer('layouts.admin', function ($view) {
            $admin = current_admin();
            $count = $admin
                ? AdminNotification::where('admin_id', $admin->id)->whereNull('read_at')->count()
                : 0;
            $view->with('unreadNotificationCount', $count);
        });
    }
}
