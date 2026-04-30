<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionPolicy extends Model
{
    protected $fillable = ['entity_type', 'retention_days', 'description', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public static function entityTypes(): array
    {
        return [
            'candidate'        => 'Candidate PII (name, identity number, mobile, remarks, scope findings)',
            'consent_record'   => 'Consent records — kept beyond candidate retention for legal proof',
            'audit_log'        => 'Audit log entries',
            'invoice'          => 'Invoices and transactions (tax records)',
        ];
    }

    /** Sensible defaults for Malaysian PDPA + tax law context. */
    public static function defaults(): array
    {
        return [
            ['entity_type' => 'candidate',      'retention_days' => 2555, 'description' => '7 years from request submission. Standard PDPA-aligned retention for screening data.', 'enabled' => true],
            ['entity_type' => 'consent_record', 'retention_days' => 3650, 'description' => '10 years. Kept longer than candidate data as legal proof of consent.', 'enabled' => true],
            ['entity_type' => 'audit_log',      'retention_days' => 2555, 'description' => '7 years. Required for SOC 2 / regulatory inquiry response.', 'enabled' => true],
            ['entity_type' => 'invoice',        'retention_days' => 2555, 'description' => '7 years. Malaysian Income Tax Act 1967 — record-keeping requirement.', 'enabled' => true],
        ];
    }
}
