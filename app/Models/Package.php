<?php

namespace App\Models;

use App\Traits\HasHashid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A named bundle of scope types offered to one customer for one country.
 *
 * Packages are authored here and are read-only in the client portal, where they
 * act as a cart shortcut in the request wizard: picking a package pre-selects
 * its scopes. Nothing downstream stores a `package_id` — the scopes are copied
 * into `candidate_scope_type` at submission — so editing a package never
 * rewrites history on requests that were already submitted.
 */
class Package extends Model
{
    use HasHashid;

    protected $fillable = ['customer_id', 'country_id', 'name', 'price'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeTypes(): BelongsToMany
    {
        return $this->belongsToMany(ScopeType::class, 'package_scope_type')
            ->orderBy('scope_types.sort_order')
            ->orderBy('scope_types.id');
    }

    /**
     * Requests ordered from this package. Deleting the package nulls their
     * `package_id` (nullOnDelete) rather than removing the requests.
     */
    public function screeningRequests(): HasMany
    {
        return $this->hasMany(ScreeningRequest::class);
    }

    /**
     * Sum of this package's scopes at the customer's effective prices, or null
     * when any scope is price-on-request with no per-customer override — the
     * client portal rejects a submission containing such a scope, so a package
     * holding one has no quotable total.
     *
     * Pass `$overrides` (customer_scope_prices keyed by scope_type_id, fetched
     * once by the caller) to avoid one query per package in a listing.
     *
     * @param  Collection<int, mixed>|null  $overrides
     */
    public function effectiveTotal(?Collection $overrides = null): ?float
    {
        $overrides ??= CustomerScopePrice::where('customer_id', $this->customer_id)
            ->pluck('price', 'scope_type_id');

        $total = 0.0;

        foreach ($this->scopeTypes as $scope) {
            if ($overrides->has($scope->id)) {
                $total += (float) $overrides->get($scope->id);

                continue;
            }

            if ($scope->price_on_request) {
                return null;
            }

            $total += (float) $scope->price;
        }

        return $total;
    }

    /**
     * Scopes in this package the customer cannot order: price-on-request with
     * no `customer_scope_prices` override. Surfaced as a warning in the admin UI
     * because the client portal blocks the whole submission on these.
     *
     * @param  Collection<int, mixed>|null  $overrides
     * @return Collection<int, ScopeType>
     */
    public function unpricedScopeTypes(?Collection $overrides = null): Collection
    {
        $overrides ??= CustomerScopePrice::where('customer_id', $this->customer_id)
            ->pluck('price', 'scope_type_id');

        return $this->scopeTypes
            ->filter(fn (ScopeType $scope) => $scope->price_on_request && ! $overrides->has($scope->id))
            ->values();
    }
}
