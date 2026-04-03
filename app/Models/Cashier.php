<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Auditable;
use Database\Factories\CashierFactory;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Cashier extends Authenticatable
{
    use Auditable;
    use BelongsToTenant;

    /** @use HasFactory<CashierFactory> */
    use HasFactory;

    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'tenant_location_id',
        'user_id',
        'name',
        'pin',
        'is_active',
        'daily_award_cap_kes',
        'total_awarded_today_kes',
        'last_login_at',
    ];

    protected $hidden = [
        'pin',
    ];

    protected $casts = [
        'pin'                     => 'hashed',
        'is_active'               => 'boolean',
        'daily_award_cap_kes'     => 'integer',
        'total_awarded_today_kes' => 'integer',
        'last_login_at'           => 'datetime',
    ];

    /**
     * Get the password for the user.
     * Overriding to use 'pin' instead of 'password'.
     */
    public function getAuthPassword(): string
    {
        return $this->pin;
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(TenantLocation::class, 'tenant_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }
}
