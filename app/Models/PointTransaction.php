<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PointTransactionFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PointTransaction extends Model
{
    use BelongsToTenant;
    /** @use HasFactory<PointTransactionFactory> */
    use HasFactory;

    use HasUuid;

    public $timestamps = false;

    protected $fillable = [
        'uuid',
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
        'points'           => 'integer',
        'balance_after'    => 'integer',
        'amount_spent_kes' => 'integer',
        'created_at'       => 'datetime',
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
