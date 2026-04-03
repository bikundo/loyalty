<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // subscription | sms_topup
            $table->unsignedBigInteger('amount'); // In cents
            $table->string('currency', 3)->default('KES');
            $table->string('status')->default('pending'); // pending | completed | failed | refunded
            $table->string('payment_method')->nullable(); // mpesa | card | bank
            $table->string('gateway')->nullable();        // flutterwave | stk_push
            $table->string('gateway_reference')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
