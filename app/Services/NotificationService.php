<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminNotification;

class NotificationService
{
    // Role mapping per notification type
    private const ROLE_MAP = [
        'new_request'        => ['super_admin', 'operations'],
        'tat_overdue'        => ['super_admin', 'operations'],
        'payment_slip'       => ['super_admin', 'finance'],
        'agreement_expiry'   => ['super_admin', 'operations', 'finance'],
        'invoice_overdue'    => ['super_admin', 'finance'],
    ];

    /**
     * Fan out a notification to all active admins of the relevant roles.
     * If $reference is set, skips admins who already have a row for that reference
     * (idempotent for system-generated alerts).
     */
    public static function fanOut(
        string $type,
        string $title,
        string $body,
        ?string $link = null,
        ?string $reference = null,
    ): void {
        $roles = self::ROLE_MAP[$type] ?? ['super_admin'];

        $admins = Admin::whereIn('role', $roles)
            ->where('status', 'active')
            ->get();

        foreach ($admins as $admin) {
            if ($reference) {
                $exists = AdminNotification::where('admin_id', $admin->id)
                    ->where('reference', $reference)
                    ->exists();
                if ($exists) {
                    continue;
                }
            }

            AdminNotification::create([
                'admin_id'  => $admin->id,
                'type'      => $type,
                'title'     => $title,
                'body'      => $body,
                'link'      => $link,
                'reference' => $reference,
            ]);
        }
    }
}
