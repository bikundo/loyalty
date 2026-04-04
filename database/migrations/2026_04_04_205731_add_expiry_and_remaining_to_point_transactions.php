<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('point_transactions', function (Blueprint $table): void {
            $table->timestamp('expires_at')->nullable()->after('note');
            $table->bigInteger('points_remaining')->default(0)->after('points'); // For FIFO tracking

            $table->index(['tenant_id', 'expires_at', 'points_remaining']);
            $table->index(['customer_id', 'expires_at', 'points_remaining']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_transactions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'expires_at', 'points_remaining']);
            $table->dropIndex(['customer_id', 'expires_at', 'points_remaining']);
            $table->dropColumn(['expires_at', 'points_remaining']);
        });
    }
};
