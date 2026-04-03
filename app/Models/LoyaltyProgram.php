<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\LoyaltyProgramFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyProgram extends Model
{
    use Auditable;
    use BelongsToTenant;

    /** @use HasFactory<LoyaltyProgramFactory> */
    use HasFactory;

    use HasUuid;

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
        'expiry_days'         => 'integer',
        'expiry_warning_days' => 'integer',
        'is_active'           => 'boolean',
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
