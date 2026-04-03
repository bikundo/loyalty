<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            // Blind index for searching encrypted phone numbers.
            // Stores a deterministic HMAC hash so we can do exact-match lookups
            // without decrypting every row.
            $table->string('phone_index', 64)->nullable()->after('phone');
            $table->index(['tenant_id', 'phone_index']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'phone_index']);
            $table->dropColumn('phone_index');
        });
    }
};
