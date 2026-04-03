<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReferral extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerReferralFactory> */
    use HasFactory, BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'referrer_customer_id',
        'referred_customer_id',
        'status',
        'qualified_at',
        'credited_at',
        'created_at',
    ];

    protected $casts = [
        'qualified_at' => 'datetime',
        'credited_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referrer_customer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_customer_id');
    }
}
