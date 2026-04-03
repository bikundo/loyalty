<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('loyalty_program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // discount | freebie | cashback | custom
            $table->unsignedBigInteger('points_required');
            $table->unsignedBigInteger('discount_value_kes')->nullable(); // cents
            $table->unsignedInteger('discount_percentage')->nullable();   // 0-100
            $table->unsignedInteger('max_redemptions_per_customer')->nullable(); // null = unlimited
            $table->unsignedInteger('max_redemptions_total')->nullable();        // null = unlimited
            $table->unsignedInteger('redemptions_count')->default(0);    // denormalised counter
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active', 'sort_order']);
            $table->index(['loyalty_program_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
