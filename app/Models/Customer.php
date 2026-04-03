<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, BelongsToTenant, HasUuid, Auditable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'phone',
        'name',
        'date_of_birth',
        'status',
        'enrolment_channel',
        'preferred_language',
        'fcm_token',
        'total_points',
        'lifetime_points_earned',
        'lifetime_spend_kes',
        'total_visits',
        'last_visit_at',
        'enrolled_at',
        'referred_by_customer_id',
    ];

    /**
     * Critical: Phone and name are encrypted at the application layer.
     */
    protected $casts = [
        'phone' => 'encrypted',
        'name' => 'encrypted',
        'date_of_birth' => 'date',
        'total_points' => 'integer',
        'lifetime_points_earned' => 'integer',
        'lifetime_spend_kes' => 'integer',
        'total_visits' => 'integer',
        'last_visit_at' => 'datetime',
        'enrolled_at' => 'datetime',
    ];

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referred_by_customer_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(CustomerReferral::class, 'referrer_customer_id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(CustomerConsent::class);
    }

    /**
     * Check if customer has enough points for a reward.
     */
    public function hasEnoughPoints(int $requiredPoints): bool
    {
        return $this->total_points >= $requiredPoints;
    }
}
