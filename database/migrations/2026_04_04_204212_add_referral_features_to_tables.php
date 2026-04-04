<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique()->after('uuid');
            $table->index('referral_code');
        });

        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->integer('referral_reward_points')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });

        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->dropColumn('referral_reward_points');
        });
    }
};
