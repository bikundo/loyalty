<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\FraudFlagFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FraudFlag extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<FraudFlagFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'flaggable_type',
        'flaggable_id',
        'reason',
        'severity',
        'status',
        'metadata',
        'resolved_at',
        'resolved_by_user_id',
    ];

    protected $casts = [
        'metadata'    => 'json',
        'resolved_at' => 'datetime',
    ];

    public function flaggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}
