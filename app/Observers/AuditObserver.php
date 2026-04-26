<?php

namespace App\Observers;

use App\Models\Admin;
use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /**
     * Field names that must never appear in plaintext in the audit log.
     * Casted/encrypted blobs are also unhelpful to readers.
     */
    private const SENSITIVE = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function created(Model $model): void
    {
        AdminAuditLog::record(
            $this->actionKey($model, 'created'),
            $model instanceof Admin ? $model : null,
            [
                'id'         => $model->getKey(),
                'attributes' => $this->scrub($model->getAttributes()),
            ],
        );
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        if (empty($changes)) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $changes);
        $diff = $this->diffChanges($changes, $original);

        // If every changed field was sensitive, we still log — but mark it.
        if (empty($diff)) {
            return;
        }

        AdminAuditLog::record(
            $this->actionKey($model, 'updated'),
            $model instanceof Admin ? $model : null,
            [
                'id'      => $model->getKey(),
                'changes' => $diff,
            ],
        );
    }

    public function deleted(Model $model): void
    {
        AdminAuditLog::record(
            $this->actionKey($model, 'deleted'),
            $model instanceof Admin ? $model : null,
            [
                'id'       => $model->getKey(),
                'snapshot' => $this->scrub($model->getAttributes()),
            ],
        );
    }

    private function actionKey(Model $model, string $verb): string
    {
        return strtolower(class_basename($model)).'.'.$verb;
    }

    private function scrub(array $attrs): array
    {
        foreach (self::SENSITIVE as $key) {
            if (array_key_exists($key, $attrs)) {
                $attrs[$key] = $attrs[$key] === null ? null : '[redacted]';
            }
        }
        return $attrs;
    }

    private function diffChanges(array $changes, array $original): array
    {
        $diff = [];
        foreach ($changes as $key => $newValue) {
            if (in_array($key, self::SENSITIVE, true)) {
                $diff[$key] = ['changed' => true]; // value masked, transition only
                continue;
            }
            $diff[$key] = [
                'from' => $original[$key] ?? null,
                'to'   => $newValue,
            ];
        }
        return $diff;
    }
}
