<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\TenantApiSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantApiSettings extends Model
{
    /** @use HasFactory<TenantApiSettingsFactory> */
    use HasFactory;
}
