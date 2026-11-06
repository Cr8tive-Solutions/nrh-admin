<?php

use App\Models\Admin;

if (! function_exists('current_admin')) {
    /**
     * Get the currently authenticated admin (cached per request).
     */
    function current_admin(): ?Admin
    {
        if (app()->bound('current_admin')) {
            return app('current_admin');
        }

        $id = session('admin_id');
        if (! $id) {
            return null;
        }

        $admin = Admin::find($id);
        app()->instance('current_admin', $admin);

        return $admin;
    }
}

if (! function_exists('admin_can')) {
    function admin_can(string $key): bool
    {
        return current_admin()?->can($key) ?? false;
    }
}

if (! function_exists('hid')) {
    function hid(int|string $id): string
    {
        return app('hashids')->encode((int) $id);
    }
}

if (! function_exists('hdecode')) {
    function hdecode(string $hash): int
    {
        $decoded = app('hashids')->decode($hash);
        abort_if(empty($decoded), 404);

        return $decoded[0];
    }
}

if (! function_exists('mask_tail')) {
    /**
     * Mask a personal identifier, keeping only its last $visible letters or
     * digits and its separators: "900101-01-1234" → "******-**-1234".
     */
    function mask_tail(?string $value, int $visible = 4): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $toMask = max(0, preg_match_all('/[\p{L}\p{N}]/u', $value) - $visible);

        return preg_replace_callback('/[\p{L}\p{N}]/u', function (array $m) use (&$toMask): string {
            return $toMask-- > 0 ? '*' : $m[0];
        }, $value);
    }
}
