<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantApiSettings extends Model
{
    /** @use HasFactory<\Database\Factories\TenantApiSettingsFactory> */
    use HasFactory, BelongsToTenant;

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
        'webhook_events' => 'json',
        'webhook_secret' => 'encrypted',
    ];
}
