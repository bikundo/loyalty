<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('points_to_kes_ratio')->default(100); // 100 points = KES 1
            $table->unsignedInteger('expiry_days')->default(365);         // 0 = never
            $table->unsignedInteger('expiry_warning_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('loyalty_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('loyalty_program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // visit | spend | milestone | birthday | referral | bonus | product | coalition
            $table->json('config');             // Rule-specific config (points, thresholds, multipliers)
            $table->boolean('is_active')->default(true);
            $table->boolean('stack_with_others')->default(true);
            $table->unsignedInteger('priority')->default(0); // Lower = evaluated first
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'type', 'is_active']);
            $table->index(['loyalty_program_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rules');
        Schema::dropIfExists('loyalty_programs');
    }
};
