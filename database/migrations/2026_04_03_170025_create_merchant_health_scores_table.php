<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_health_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedInteger('score'); // 0-100
            $table->json('metrics'); // retention, activity, churn risk
            $table->date('calculated_for_date');
            $table->timestamps();

            $table->unique(['tenant_id', 'calculated_for_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_health_scores');
    }
};
