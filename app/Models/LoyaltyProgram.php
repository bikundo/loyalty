<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyProgram extends Model
{
    /** @use HasFactory<\Database\Factories\LoyaltyProgramFactory> */
    use HasFactory, HasUuid, BelongsToTenant, Auditable;

    protected $fillable = [
        'tenant_id',
        'name',
        'points_to_kes_ratio',
        'expiry_days',
        'expiry_warning_days',
        'is_active',
    ];

    protected $casts = [
        'points_to_kes_ratio' => 'integer',
        'expiry_days' => 'integer',
        'expiry_warning_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(LoyaltyRule::class);
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }
}
