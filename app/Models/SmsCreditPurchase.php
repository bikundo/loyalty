<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SmsCreditPurchaseFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsCreditPurchase extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<SmsCreditPurchaseFactory> */
    use HasFactory;

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
        'amount_paid'       => 'integer',
        'created_at'        => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(SmsWallet::class);
    }
}
