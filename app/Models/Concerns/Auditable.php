<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * Writes immutable audit log entries on model updates and soft deletes.
 *
 * Applied to business-critical models where every change must be traceable:
 * LoyaltyRule, Reward, Cashier, TenantApiKey, Subscription.
 *
 * Point transactions are immutable by design (voids create new entries)
 * and do not need this trait.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::updated(function (self $model): void {
            AuditLog::record(
                model: $model,
                action: strtolower(class_basename($model)) . '.updated',
                before: $model->getOriginal(),
                after: $model->getDirty(),
            );
        });

        static::deleted(function (self $model): void {
            AuditLog::record(
                model: $model,
                action: strtolower(class_basename($model)) . '.deleted',
                before: $model->toArray(),
                after: null,
            );
        });
    }
}
