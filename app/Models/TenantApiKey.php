<?php

namespace App\Models;

use Illuminate\Support\Str;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\Auditable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TenantApiKeyFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TenantApiKey extends Model
{
    use Auditable;
    use BelongsToTenant;

    /** @use HasFactory<TenantApiKeyFactory> */
    use HasFactory;

    use HasUuid;

    protected $fillable = [
        'tenant_id',
        'created_by_user_id',
        'name',
        'key_prefix',
        'key_hash',
        'type',
        'is_active',
        'last_used_at',
        'revoked_at',
        'rotation_expires_at',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'last_used_at'        => 'datetime',
        'revoked_at'          => 'datetime',
        'rotation_expires_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Generate a new API key pair.
     * Returns the plain-text key (only visible once).
     */
    public static function generate(Tenant $tenant, string $name, ?User $user = null): array
    {
        $plainKey = 'lk_' . Str::random(32);
        $prefix = substr($plainKey, 0, 8);

        $key = self::create([
            'tenant_id'          => $tenant->id,
            'created_by_user_id' => $user?->id,
            'name'               => $name,
            'key_prefix'         => $prefix,
            'key_hash'           => Hash::make($plainKey),
        ]);

        return [
            'key'        => $key,
            'plain_text' => $plainKey,
        ];
    }

    /**
     * Verify a plain-text key against this record.
     */
    public function verify(string $plainKey): bool
    {
        return Hash::check($plainKey, $this->key_hash);
    }
}
