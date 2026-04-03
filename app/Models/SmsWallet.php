<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsWallet extends Model
{
    /** @use HasFactory<\Database\Factories\SmsWalletFactory> */
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'credits_balance',
        'credits_reserved',
        'credits_used_total',
        'low_balance_alerted_at',
    ];

    protected $casts = [
        'credits_balance' => 'integer',
        'credits_reserved' => 'integer',
        'credits_used_total' => 'integer',
        'low_balance_alerted_at' => 'datetime',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(SmsCreditPurchase::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class);
    }

    public function hasCredits(int $amount): bool
    {
        return $this->credits_balance >= $amount;
    }
}
