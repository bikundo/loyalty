<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\CustomerReferralFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerReferral extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CustomerReferralFactory> */
    use HasFactory;

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
        'credited_at'  => 'datetime',
        'created_at'   => 'datetime',
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
