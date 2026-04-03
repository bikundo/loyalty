<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ApiRequestLogFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiRequestLog extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ApiRequestLogFactory> */
    use HasFactory;

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
        'status_code'      => 'integer',
        'response_time_ms' => 'integer',
        'request_body'     => 'json',
        'created_at'       => 'datetime',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(TenantApiKey::class, 'tenant_api_key_id');
    }
}
