<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationQueue extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationQueueFactory> */
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'notifiable_type',
        'notifiable_id',
        'type',
        'content',
        'status',
        'retry_count',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
