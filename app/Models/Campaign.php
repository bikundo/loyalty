<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Campaign extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'tenant_id',
        'created_by_user_id',
        'name',
        'message',
        'segment_type',
        'segment_config',
        'status',
        'recipients_total',
        'recipients_sent',
        'recipients_delivered',
        'recipients_failed',
        'credits_reserved',
        'credits_used',
        'scheduled_at',
        'dispatched_at',
        'completed_at',
    ];

    protected $casts = [
        'segment_config'       => 'json',
        'recipients_total'     => 'integer',
        'recipients_sent'      => 'integer',
        'recipients_delivered' => 'integer',
        'recipients_failed'    => 'integer',
        'credits_reserved'     => 'integer',
        'credits_used'         => 'integer',
        'scheduled_at'         => 'datetime',
        'dispatched_at'        => 'datetime',
        'completed_at'         => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
