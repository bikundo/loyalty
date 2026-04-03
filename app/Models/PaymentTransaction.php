<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentTransaction extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<PaymentTransactionFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'amount',
        'currency',
        'status',
        'payment_method',
        'gateway',
        'gateway_reference',
        'gateway_response',
    ];

    protected $casts = [
        'amount'           => 'integer',
        'gateway_response' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
