<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MerchantHealthScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MerchantHealthScore extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<MerchantHealthScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'score',
        'metrics',
        'calculated_for_date',
    ];

    protected $casts = [
        'score'               => 'integer',
        'metrics'             => 'json',
        'calculated_for_date' => 'date',
    ];
}
