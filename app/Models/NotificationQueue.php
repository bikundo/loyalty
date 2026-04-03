<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\NotificationQueueFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationQueue extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<NotificationQueueFactory> */
    use HasFactory;

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
        'retry_count'  => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
