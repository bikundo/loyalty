<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiRequestLog extends Model
{
    /** @use HasFactory<\Database\Factories\ApiRequestLogFactory> */
    use HasFactory, BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'tenant_api_key_id',
        'method',
        'endpoint',
        'status_code',
        'response_time_ms',
        'ip_address',
        'request_body',
        'created_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'response_time_ms' => 'integer',
        'request_body' => 'json',
        'created_at' => 'datetime',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(TenantApiKey::class, 'tenant_api_key_id');
    }
}
