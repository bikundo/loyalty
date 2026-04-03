<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\TenantApiKeyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantApiKey extends Model
{
    /** @use HasFactory<TenantApiKeyFactory> */
    use HasFactory;
}
