<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Redemption extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'customer_id',
        'reward_id',
        'tenant_location_id',
        'point_transaction_id',
        'initiated_by_cashier_id',
        'confirmed_by_cashier_id',
        'confirmed_by_user_id',
        'status',
        'points_used',
        'rejection_reason',
        'confirmed_at',
        'rejected_at',
    ];

    protected $casts = [
        'points_used'  => 'integer',
        'confirmed_at' => 'datetime',
        'rejected_at'  => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(TenantLocation::class, 'tenant_location_id');
    }

    public function pointTransaction(): BelongsTo
    {
        return $this->belongsTo(PointTransaction::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(Cashier::class, 'initiated_by_cashier_id');
    }

    public function confirmedByCashier(): BelongsTo
    {
        return $this->belongsTo(Cashier::class, 'confirmed_by_cashier_id');
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
