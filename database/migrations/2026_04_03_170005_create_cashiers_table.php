<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('cashiers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tenant_location_id')->nullable()->constrained('tenant_locations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // optional link to web user
            $table->string('name');
            $table->string('pin');                           // bcrypt hash of 4-6 digit PIN
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('daily_award_cap_kes')->default(50000_00); // stored in cents
            $table->unsignedBigInteger('total_awarded_today_kes')->default(0);   // cents, reset daily
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'tenant_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashiers');
    }
};
