<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\CustomerConsentFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerConsent extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CustomerConsentFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'consent_type',
        'channel',
        'consent_version',
        'ip_address',
        'consented_at',
        'revoked_at',
        'created_at',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'revoked_at'   => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
