<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('tenant_location_id')->nullable()->constrained('tenant_locations')->nullOnDelete();
            $table->foreignId('loyalty_rule_id')->nullable()->constrained('loyalty_rules')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('cashiers')->nullOnDelete();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('triggered_by')->nullable(); // cashier | dashboard | api | ussd | system
            $table->string('type');     // earn | redeem | expire | adjust | void | coalition_earn | coalition_redeem
            $table->bigInteger('points');              // Positive for earn, negative for redeem/expire/void
            $table->unsignedBigInteger('balance_after'); // Denormalised snapshot — immutable audit trail
            $table->unsignedBigInteger('amount_spent_kes')->nullable(); // cents — for spend rules
            $table->string('external_reference')->nullable(); // POS receipt, M-Pesa ref
            $table->string('idempotency_key')->nullable()->unique(); // Prevents double-award on retry
            $table->text('note')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_transaction_id')->nullable()->constrained('point_transactions')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — this is an immutable ledger

            $table->index(['tenant_id', 'customer_id', 'created_at']);
            $table->index(['tenant_id', 'type', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['tenant_id', 'cashier_id', 'created_at']);
            $table->index(['tenant_id', 'tenant_location_id', 'created_at']);
            $table->index('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
