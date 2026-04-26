<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // @allowed('invoice.manage') ... @endallowed — RBAC/UBAC permission check.
        Blade::if('allowed', function (string $key) {
            return admin_can($key);
        });
    }
}
