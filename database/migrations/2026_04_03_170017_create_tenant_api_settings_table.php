<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_api_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->boolean('api_access_enabled')->default(false);
            $table->unsignedInteger('rate_limit_per_day')->default(0); // 0 = no access
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->json('webhook_events')->nullable(); // events to fire
            $table->timestamps();
        });

        Schema::create('tenant_api_keys', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('key_prefix', 8)->index();     // First 8 chars for fast lookup
            $table->string('key_hash');                   // Full bcrypt hash for verification
            $table->string('type')->default('api_key');   // api_key | oauth2_client
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('rotation_expires_at')->nullable(); // Grace period during key rotation
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('api_request_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tenant_api_key_id')->nullable()->constrained('tenant_api_keys')->nullOnDelete();
            $table->string('method', 10);
            $table->string('endpoint');
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('response_time_ms');
            $table->string('ip_address', 45)->nullable();
            $table->json('request_body')->nullable();        // Sanitised — no PII
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — append-only. Purged after 90 days.

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'endpoint', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
        Schema::dropIfExists('tenant_api_keys');
        Schema::dropIfExists('tenant_api_settings');
    }
};
