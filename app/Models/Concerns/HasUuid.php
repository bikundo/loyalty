<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Auto-generates a UUID on model creation and uses it as the route key.
 *
 * Applied to all models that expose IDs externally (API responses, QR codes,
 * URLs). Internal joins and FKs still use integer IDs for performance.
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Route model binding resolves by UUID, not integer id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
