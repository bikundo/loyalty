<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyRule extends Model
{
    /** @use HasFactory<\Database\Factories\LoyaltyRuleFactory> */
    use HasFactory, HasUuid, BelongsToTenant, Auditable, SoftDeletes;

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
        'config' => 'json',
        'is_active' => 'boolean',
        'stack_with_others' => 'boolean',
        'priority' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }
}
