<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_admin_id', 'target_admin_id', 'action', 'details', 'ip_address', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'actor_admin_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'target_admin_id');
    }

    /**
     * Write an audit entry. Actor is the currently logged-in admin (if any),
     * IP + user agent are pulled from the current request.
     */
    public static function record(string $action, ?Admin $target = null, array $details = []): self
    {
        $request = request();

        return self::create([
            'actor_admin_id'  => current_admin()?->id,
            'target_admin_id' => $target?->id,
            'action'          => $action,
            'details'         => $details ?: null,
            'ip_address'      => $request?->ip(),
            'user_agent'      => $request?->userAgent(),
            'created_at'      => now(),
        ]);
    }
}
