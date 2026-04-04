<?php

namespace App\Models;

use Database\Factories\SmsLogFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsLog extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<SmsLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'campaign_id',
        'phone',
        'message',
        'direction',
        'type',
        'gateway',
        'gateway_message_id',
        'sender_id',
        'status',
        'failure_reason',
        'credits_used',
        'sent_at',
        'delivered_at',
        'failed_at',
        'created_at',
    ];

    protected $casts = [
        'credits_used' => 'integer',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at'    => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
