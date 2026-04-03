<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\LoyaltyRuleFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyRule extends Model
{
    use Auditable;
    use BelongsToTenant;

    /** @use HasFactory<LoyaltyRuleFactory> */
    use HasFactory;

    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'loyalty_program_id',
        'name',
        'type',
        'config',
        'is_active',
        'stack_with_others',
        'priority',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'config'            => 'json',
        'is_active'         => 'boolean',
        'stack_with_others' => 'boolean',
        'priority'          => 'integer',
        'start_date'        => 'date',
        'end_date'          => 'date',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }
}
