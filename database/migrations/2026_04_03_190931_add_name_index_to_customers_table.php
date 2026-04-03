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
        Schema::table('customers', function (Blueprint $table): void {
            // Blind index for searching encrypted names.
            // Stores a deterministic HMAC hash of the lowercase name.
            $table->string('name_index', 64)->nullable()->after('name');
            $table->index(['tenant_id', 'name_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'name_index']);
            $table->dropColumn('name_index');
        });
    }
};
