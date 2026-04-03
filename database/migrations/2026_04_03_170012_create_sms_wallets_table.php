<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('sms_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('credits_balance')->default(0);
            $table->unsignedBigInteger('credits_reserved')->default(0); // In-flight sends
            $table->unsignedBigInteger('credits_used_total')->default(0);
            $table->timestamp('low_balance_alerted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_credit_purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sms_wallet_id')->constrained('sms_wallets')->cascadeOnDelete();
            $table->unsignedBigInteger('credits_purchased');
            $table->unsignedBigInteger('amount_paid');      // cents
            $table->string('currency', 3)->default('KES');
            $table->string('payment_reference')->nullable();
            $table->string('gateway')->default('flutterwave');
            $table->string('status')->default('completed'); // completed | failed | refunded
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_credit_purchases');
        Schema::dropIfExists('sms_wallets');
    }
};
