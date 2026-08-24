<?php

namespace App\Http\Middleware;

use App\Support\AuthSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('admin_id')) {
            return redirect()->route('login');
        }

        // Re-validate the account on every request: a deactivated (or deleted)
        // admin must lose access immediately, not merely at their next login.
        $admin = current_admin();
        if (! $admin || $admin->status !== 'active') {
            AuthSession::logout();

            return redirect()->route('login')
                ->with('error', 'Your account is no longer active. Please contact an administrator.');
        }

        return $next($request);
    }
}
