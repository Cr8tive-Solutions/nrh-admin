<?php

namespace App\Traits;

trait HasHashid
{
    public function getRouteKey(): mixed
    {
        return app('hashids')->encode($this->getKey());
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $decoded = app('hashids')->decode($value);

        abort_if(empty($decoded), 404);

        return $this->where($field ?? $this->getRouteKeyName(), $decoded[0])->firstOrFail();
    }

    public function resolveChildRouteBinding($childType, $value, $field): ?static
    {
        return $this->resolveRouteBinding($value, $field);
    }
}
