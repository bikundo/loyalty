<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantHealthScore extends Model
{
    /** @use HasFactory<\Database\Factories\MerchantHealthScoreFactory> */
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'score',
        'metrics',
        'calculated_for_date',
    ];

    protected $casts = [
        'score' => 'integer',
        'metrics' => 'json',
        'calculated_for_date' => 'date',
    ];
}
