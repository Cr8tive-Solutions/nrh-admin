<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Setting;
use App\Models\AdminNotification;
use App\Models\CustomerRegistration;
use App\Models\Agreement;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerScopePrice;
use App\Models\CustomerUser;
use App\Models\CustomerUserInvitation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePaymentReceipt;
use App\Models\Package;
use App\Models\Permission;
use App\Models\RequestCandidate;
use App\Models\ScopeType;
use App\Models\ScreeningRequest;
use App\Models\Transaction;
use App\Observers\AuditObserver;
use App\Observers\PaymentReceiptNotificationObserver;
use App\Observers\ScreeningRequestNotificationObserver;
use App\Support\EncryptedFilesystemAdapter;
use Hashids\Hashids;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter as LocalAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Salt falls back to APP_KEY for existing URLs; set HASHIDS_SALT to
        // decouple URL ids from key rotation (see config/hashids.php).
        $this->app->singleton('hashids', fn () => new Hashids(
            config('hashids.salt') ?: config('app.key'),
            (int) config('hashids.min_length', 8),
        ));

        // `artisan serve` (the Sail container's web server) strips every
        // non-allow-listed env var from its PHP workers whenever a .env file
        // exists. compose.yaml sets CLIENT_STORAGE_PATH in `environment:`, so
        // without this the workers fell back to the sibling path and every
        // client-side file download 404'd.
        ServeCommand::$passthroughVariables[] = 'CLIENT_STORAGE_PATH';
    }

    public function boot(): void
    {
        $this->registerLocalMountDriver();

        // SST rate is admin-configurable (app_settings); override the static
        // config default so every config('billing.sst_rate') call site uses it.
        config(['billing.sst_rate' => Setting::sstRate((float) config('billing.sst_rate', 0.06))]);

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
            Package::class,
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

            // Customer registrations waiting for staff to verify and approve.
            $view->with('pendingRegistrationCount', $admin?->can('customer.manage')
                ? CustomerRegistration::where('status', 'submitted')->count()
                : 0);
        });
    }

    /**
     * A `local` driver that does NOT create its root directory on resolution.
     *
     * Flysystem's LocalFilesystemAdapter creates its root eagerly in the
     * constructor, and Laravel's built-in local driver gives no way to opt out.
     * For the cross-app mount disks (`client_local` here, `admin_local` on the
     * client portal) that means merely calling Storage::disk() throws
     * UnableToCreateDirectory whenever the mount is absent — which is the normal
     * case in Docker, where the sibling-path default cannot resolve.
     *
     * That defeated the graceful fallbacks the download controllers already
     * have: they try the mount, then the local disk, then 404 with a message
     * naming the mount. With lazy root creation the disk resolves, `exists()`
     * simply returns false, and a missing mount degrades to that clean 404
     * instead of a 500 — and nothing is ever created in the wrong place.
     */
    private function registerLocalMountDriver(): void
    {
        Storage::extend('local_mount', function ($app, array $config) {
            $visibility = PortableVisibilityConverter::fromArray(
                $config['permissions'] ?? [],
                $config['directory_visibility'] ?? $config['visibility'] ?? Visibility::PRIVATE
            );

            $links = ($config['links'] ?? null) === 'skip'
                ? LocalAdapter::SKIP_LINKS
                : LocalAdapter::DISALLOW_LINKS;

            $adapter = new LocalAdapter(
                $config['root'],
                $visibility,
                $config['lock'] ?? LOCK_EX,
                $links,
                null,
                lazyRootCreation: true,
            );

            // 'encrypt' => true seals files at rest (see EncryptedFilesystemAdapter).
            if ($config['encrypt'] ?? false) {
                $adapter = new EncryptedFilesystemAdapter($adapter);
            }

            return new LocalFilesystemAdapter(
                new Flysystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
