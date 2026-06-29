<?php

namespace App\Models;

use App\Traits\HasHashid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScopeType extends Model
{
    use HasHashid;

    /**
     * Documents a customer may be required to upload for a scope. The client
     * portal reads `required_documents` and enforces the upload at submission.
     */
    public const DOCUMENT_TYPES = [
        'consent' => 'Signed Consent Form',
        'nric' => 'NRIC / ID Copy',
        'resume' => 'Resume / CV',
        'certificate' => 'Certificate Copy',
    ];

    protected $fillable = [
        'country_id', 'name', 'category', 'sort_order', 'turnaround', 'turnaround_hours',
        'price', 'price_on_request', 'description', 'requires_signed_consent', 'required_documents',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_on_request' => 'boolean',
        'requires_signed_consent' => 'boolean',
        'turnaround_hours' => 'integer',
        'required_documents' => 'array',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Human-readable labels for this scope's required documents, in the
     * canonical order defined by DOCUMENT_TYPES.
     *
     * @return list<string>
     */
    public function requiredDocumentLabels(): array
    {
        $keys = $this->required_documents ?? [];

        return collect(self::DOCUMENT_TYPES)
            ->filter(fn ($label, $key) => in_array($key, $keys, true))
            ->values()
            ->all();
    }

    public function customerScopePrice(int $customerId): ?CustomerScopePrice
    {
        return CustomerScopePrice::where('customer_id', $customerId)
            ->where('scope_type_id', $this->id)
            ->first();
    }

    public function effectivePrice(int $customerId): string
    {
        $custom = $this->customerScopePrice($customerId);
        if ($custom) {
            return number_format($custom->price, 2);
        }
        if ($this->price_on_request) {
            return 'Price on request';
        }

        return number_format($this->price, 2);
    }
}
