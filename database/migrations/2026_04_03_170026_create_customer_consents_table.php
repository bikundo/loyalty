<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('customer_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('consent_type')->default('enrolment'); // enrolment | marketing | whatsapp
            $table->string('channel');  // dashboard | sms | app | api
            $table->string('consent_version')->default('1.0');
            $table->string('ip_address')->nullable();
            $table->timestamp('consented_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'customer_id', 'consent_type']);
        });

        Schema::create('customer_referrals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('referrer_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('referred_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending | qualified | credited | expired
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('credited_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'referrer_customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_referrals');
        Schema::dropIfExists('customer_consents');
    }
};
