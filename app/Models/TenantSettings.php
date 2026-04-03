<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TenantSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantSettings extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TenantSettingsFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'programme_name',
        'points_name',
        'logo_url',
        'brand_color_primary',
        'brand_color_secondary',
        'sms_language',
        'sms_sender_id',
        'sms_sender_id_status',
        'join_keyword',
        'join_code',
        'points_expiry_days',
        'expiry_warning_days',
        'enable_expiry_warning_sms',
        'enable_ussd_channel',
        'low_wallet_alert_threshold',
    ];

    protected $casts = [
        'points_expiry_days'         => 'integer',
        'expiry_warning_days'        => 'integer',
        'enable_expiry_warning_sms'  => 'boolean',
        'enable_ussd_channel'        => 'boolean',
        'low_wallet_alert_threshold' => 'integer',
    ];
}
