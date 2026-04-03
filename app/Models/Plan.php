<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Auditable;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use Auditable;

    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'price_amount',
        'currency',
        'billing_interval',
        'sms_wallet_topup_bonus_pct',
        'max_locations',
        'max_cashiers',
        'api_access_enabled',
        'ussd_enabled',
        'coalition_enabled',
        'branded_app_enabled',
        'rate_limit_per_day',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_amount'               => 'integer',
        'sms_wallet_topup_bonus_pct' => 'integer',
        'max_locations'              => 'integer',
        'max_cashiers'               => 'integer',
        'api_access_enabled'         => 'boolean',
        'ussd_enabled'               => 'boolean',
        'coalition_enabled'          => 'boolean',
        'branded_app_enabled'        => 'boolean',
        'rate_limit_per_day'         => 'integer',
        'features'                   => 'json',
        'is_active'                  => 'boolean',
        'sort_order'                 => 'integer',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
