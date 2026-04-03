<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCreditPurchase extends Model
{
    /** @use HasFactory<\Database\Factories\SmsCreditPurchaseFactory> */
    use HasFactory, BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'sms_wallet_id',
        'credits_purchased',
        'amount_paid',
        'currency',
        'payment_reference',
        'gateway',
        'status',
        'created_at',
    ];

    protected $casts = [
        'credits_purchased' => 'integer',
        'amount_paid' => 'integer',
        'created_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(SmsWallet::class);
    }
}
