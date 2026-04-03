<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');                          // Starter, Growth, Business, Enterprise
            $table->string('slug')->unique();
            $table->unsignedInteger('price_amount');         // In KES (stored as integer cents)
            $table->string('currency', 3)->default('KES');
            $table->string('billing_interval')->default('monthly'); // monthly | annual
            $table->unsignedInteger('sms_wallet_topup_bonus_pct')->default(0); // % bonus credits on top-up
            $table->unsignedInteger('max_locations')->default(1);
            $table->unsignedInteger('max_cashiers')->default(5);
            $table->boolean('api_access_enabled')->default(false);
            $table->boolean('ussd_enabled')->default(false);
            $table->boolean('coalition_enabled')->default(false);
            $table->boolean('branded_app_enabled')->default(false);
            $table->unsignedInteger('rate_limit_per_day')->default(0); // 0 = no API access
            $table->json('features')->nullable();            // Additional feature flags
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
