<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\NotificationQueueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationQueue extends Model
{
    /** @use HasFactory<NotificationQueueFactory> */
    use HasFactory;
}
