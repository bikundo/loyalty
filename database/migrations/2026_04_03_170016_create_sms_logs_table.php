<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('phone');                        // Recipient phone (encrypted at app layer)
            $table->text('message');
            $table->string('direction')->default('outbound'); // outbound | inbound
            $table->string('type')->default('transactional'); // transactional | campaign | otp | welcome | expiry
            $table->string('gateway')->default('africastalking'); // africastalking | infobip | whatsapp
            $table->string('gateway_message_id')->nullable();
            $table->string('sender_id')->nullable();
            $table->string('status')->default('pending'); // pending | sent | delivered | failed | skipped
            $table->string('failure_reason')->nullable();
            $table->unsignedInteger('credits_used')->default(1);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — append-only log

            $table->index(['tenant_id', 'customer_id', 'created_at']);
            $table->index(['tenant_id', 'type', 'created_at']);
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['campaign_id', 'status']);
            $table->index('gateway_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
