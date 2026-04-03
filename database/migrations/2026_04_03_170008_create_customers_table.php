<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('phone');                         // Encrypted at application layer
            $table->string('name')->nullable();              // Encrypted at application layer
            $table->string('email')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('status')->default('active');     // active | inactive | blocked
            $table->string('enrolment_channel')->default('dashboard'); // dashboard | sms | app | api
            $table->string('preferred_language')->default('en');
            $table->string('fcm_token')->nullable();         // Firebase push token

            // Denormalised for O(1) reads (scanner app <200ms requirement)
            $table->unsignedBigInteger('total_points')->default(0);
            $table->unsignedBigInteger('lifetime_points_earned')->default(0);
            $table->unsignedBigInteger('lifetime_spend_kes')->default(0); // cents
            $table->unsignedInteger('total_visits')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamp('enrolled_at')->useCurrent();

            // Referral tracking
            $table->foreignId('referred_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
            $table->softDeletes();

            // Tenant isolation index — all queries tenant-scoped
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'last_visit_at']);
            $table->index(['tenant_id', 'enrolled_at']);
            // Raw phone lookup (encrypted — cannot use LIKE, must match exact ciphertext)
            $table->index(['tenant_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
