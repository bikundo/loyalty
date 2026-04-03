<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\CampaignRecipientFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CampaignRecipient extends Model
{
    /** @use HasFactory<CampaignRecipientFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'customer_id',
        'status',
        'sent_at',
        'delivered_at',
        'failed_at',
        'created_at',
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at'    => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
