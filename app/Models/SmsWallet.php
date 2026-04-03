<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SmsWalletFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsWallet extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<SmsWalletFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'credits_balance',
        'credits_reserved',
        'credits_used_total',
        'low_balance_alerted_at',
    ];

    protected $casts = [
        'credits_balance'        => 'integer',
        'credits_reserved'       => 'integer',
        'credits_used_total'     => 'integer',
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
