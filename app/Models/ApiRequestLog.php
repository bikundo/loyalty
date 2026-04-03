<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\ApiRequestLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiRequestLog extends Model
{
    /** @use HasFactory<ApiRequestLogFactory> */
    use HasFactory;
}
