<?php

namespace App\Models;

use App\Traits\HasHashid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportVersion extends Model
{
    use HasHashid;
    protected $fillable = [
        'screening_request_id', 'type', 'version',
        'generated_at', 'generated_by_admin_id',
        'file_path', 'file_sha256', 'content_hash',
        'snapshot', 'supersedes_id', 'supersede_reason',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'snapshot'     => 'array',
    ];

    public function screeningRequest(): BelongsTo
    {
        return $this->belongsTo(ScreeningRequest::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'generated_by_admin_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    public function supersededBy(): HasMany
    {
        return $this->hasMany(self::class, 'supersedes_id');
    }

    /**
     * Allowed report types in canonical order.
     */
    public static function types(): array
    {
        return ['prelim', 'full'];
    }

    public function label(): string
    {
        $typeLabel = ($this->type === 'full' && $this->supersedes_id) ? 'Updated' : ucfirst($this->type);

        return $typeLabel.' v'.$this->version;
    }

    public function shortHash(): string
    {
        return substr($this->file_sha256, 0, 8);
    }

    public function isSuperseded(): bool
    {
        return $this->supersededBy()->exists();
    }
}
