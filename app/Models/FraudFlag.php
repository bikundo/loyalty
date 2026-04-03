<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FraudFlag extends Model
{
    /** @use HasFactory<\Database\Factories\FraudFlagFactory> */
    use HasFactory, BelongsToTenant;

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
        'metadata' => 'json',
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
