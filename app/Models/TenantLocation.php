<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\TenantLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantLocation extends Model
{
    /** @use HasFactory<TenantLocationFactory> */
    use HasFactory;
}
