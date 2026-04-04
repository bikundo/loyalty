<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
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
        // 1. Decrypt data
        $this->decryptTable('customers', ['phone', 'name']);
        $this->decryptTable('sms_logs', ['phone']);
        $this->decryptTable('tenant_api_settings', ['webhook_secret']);

        // 2. Drop blind index columns and add standard indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['phone_index', 'name_index']);
            $table->index('phone');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropIndex(['name']);
            $table->string('phone_index')->nullable()->after('phone');
            $table->string('name_index')->nullable()->after('name');
        });
    }

    private function decryptTable(string $table, array $columns): void
    {
        DB::table($table)->orderBy('id')->chunk(100, function ($rows) use ($table, $columns) {
            foreach ($rows as $row) {
                $updates = [];
                foreach ($columns as $column) {
                    $value = $row->{$column};
                    if ($value && str_contains($value, 'eyJpdiI6')) {
                        try {
                            $updates[$column] = Crypt::decryptString($value);
                        }
                        catch (Exception $e) {
                            // Skip if decryption fails
                        }
                    }
                }

                if (!empty($updates)) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        });
    }
};
