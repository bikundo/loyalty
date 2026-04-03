<?php

namespace App\Services;

use App\Models\Tenant;

/**
 * Holds the resolved tenant for the current request lifecycle.
 *
 * Bound as a singleton in AppServiceProvider. The BelongsToTenant global
 * scope reads from this service on every query. Null means super admin
 * context — no tenant scope is applied.
 */
class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function forget(): void
    {
        $this->tenant = null;
    }

    public function isSet(): bool
    {
        return $this->tenant !== null;
    }
}
