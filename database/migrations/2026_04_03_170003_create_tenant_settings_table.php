<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('programme_name')->default('Loyalty Programme');
            $table->string('points_name')->default('points');         // e.g. "stars", "stamps"
            $table->string('logo_url')->nullable();
            $table->string('brand_color_primary', 7)->default('#000000');
            $table->string('brand_color_secondary', 7)->default('#ffffff');
            $table->string('sms_language')->default('en');            // en | sw
            $table->string('sms_sender_id')->default('LOYALTYOS');
            $table->string('sms_sender_id_status')->default('default'); // default | pending | approved
            $table->string('join_keyword')->nullable()->unique();     // e.g. "MAMA"
            $table->string('join_code', 8)->nullable()->unique();     // Short alphanumeric for app enrolment
            $table->unsignedInteger('points_expiry_days')->default(365); // 0 = never
            $table->unsignedInteger('expiry_warning_days')->default(30);
            $table->boolean('enable_expiry_warning_sms')->default(true);
            $table->boolean('enable_ussd_channel')->default(false);
            $table->unsignedInteger('low_wallet_alert_threshold')->default(100); // credits
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
