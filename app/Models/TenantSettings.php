<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\TenantSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantSettings extends Model
{
    /** @use HasFactory<TenantSettingsFactory> */
    use HasFactory;
}
