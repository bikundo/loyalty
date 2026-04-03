<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Automatically scopes all queries to the current tenant.
 *
 * Applied to every tenant-scoped model. Injects WHERE tenant_id = ?
 * on all reads and auto-sets tenant_id on create. Super admin context
 * (tenant_id = null) bypasses the scope transparently.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = app('tenant.context')->id();

            if ($tenantId !== null) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    $tenantId
                );
            }
        });

        static::creating(function (self $model): void {
            if (empty($model->tenant_id)) {
                $tenantId = app('tenant.context')->id();

                if ($tenantId !== null) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Bypass tenant scoping for the current query.
     * Use in super admin contexts only.
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
