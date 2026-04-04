<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TenantApiSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantApiSettings extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TenantApiSettingsFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'api_access_enabled',
        'rate_limit_per_day',
        'webhook_url',
        'webhook_secret',
        'webhook_events',
    ];

    protected $casts = [
        'api_access_enabled' => 'boolean',
        'rate_limit_per_day' => 'integer',
        'webhook_events'     => 'json',
    ];
}
