<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Auditable;
use Database\Factories\RewardFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reward extends Model
{
    use Auditable;
    use BelongsToTenant;

    /** @use HasFactory<RewardFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'loyalty_program_id',
        'name',
        'description',
        'type',
        'points_required',
        'discount_value_kes',
        'discount_percentage',
        'max_redemptions_per_customer',
        'max_redemptions_total',
        'redemptions_count',
        'image_url',
        'is_active',
        'sort_order',
        'expires_at',
    ];

    protected $casts = [
        'points_required'              => 'integer',
        'discount_value_kes'           => 'integer',
        'discount_percentage'          => 'integer',
        'max_redemptions_per_customer' => 'integer',
        'max_redemptions_total'        => 'integer',
        'redemptions_count'            => 'integer',
        'is_active'                    => 'boolean',
        'sort_order'                   => 'integer',
        'expires_at'                   => 'date',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(Redemption::class);
    }
}
