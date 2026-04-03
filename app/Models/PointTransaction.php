<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\PointTransactionFactory> */
    use HasFactory, BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'tenant_location_id',
        'loyalty_rule_id',
        'cashier_id',
        'triggered_by_user_id',
        'triggered_by',
        'type',
        'points',
        'balance_after',
        'amount_spent_kes',
        'external_reference',
        'idempotency_key',
        'note',
        'void_reason',
        'voided_transaction_id',
        'created_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'amount_spent_kes' => 'integer',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(TenantLocation::class, 'tenant_location_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(LoyaltyRule::class, 'loyalty_rule_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Cashier::class);
    }

    public function triggerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    public function voidedTransaction(): BelongsTo
    {
        return $this->belongsTo(PointTransaction::class, 'voided_transaction_id');
    }
}
