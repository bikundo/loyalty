<?php

namespace App\Services\Sms;

use App\Models\Tenant;
use App\Models\Customer;
use App\Models\SmsLog;
use App\Models\SmsWallet;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService
{
    public function __construct(
        protected SmsProviderInterface $provider
    ) {}

    /**
     * Send a transactional SMS to a customer.
     *
     * @param Customer $customer
     * @param string $message
     * @param array $meta
     * @return SmsLog|null
     */
    public function sendToCustomer(Customer $customer, string $message, array $meta = []): ?SmsLog
    {
        return $this->dispatch($customer->tenant, $customer->phone, $message, $customer, $meta);
    }

    /**
     * Internal dispatch logic with credit checking and logging.
     *
     * @param Tenant $tenant
     * @param string $to
     * @param string $message
     * @param Customer|null $customer
     * @param array $meta
     * @return SmsLog|null
     */
    protected function dispatch(Tenant $tenant, string $to, string $message, ?Customer $customer = null, array $meta = []): ?SmsLog
    {
        // One credit per 160 characters (basic estimation)
        $creditsRequired = (int) ceil(strlen($message) / 160);

        return DB::transaction(function () use ($tenant, $to, $message, $customer, $meta, $creditsRequired) {
            /** @var SmsWallet $wallet */
            $wallet = SmsWallet::lockForUpdate()->firstOrCreate(
                ['tenant_id' => $tenant->id],
                ['credits_balance' => 0, 'credits_reserved' => 0]
            );

            if (!$wallet->hasCredits($creditsRequired)) {
                Log::warning("Insufficient credits for Tenant {$tenant->id} [{$tenant->name}]. Required: {$creditsRequired}, Balance: {$wallet->credits_balance}");
                return null;
            }

            // Create log entry as "pending"
            $log = SmsLog::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer?->id,
                'phone' => $to,
                'message' => $message,
                'credits_deducted' => $creditsRequired,
                'status' => 'pending',
                'provider' => 'africastalking',
                'triggered_by' => $meta['triggered_by'] ?? 'system',
                'meta' => json_encode($meta),
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            // Attempt delivery
            $result = $this->provider->send($to, $message, $tenant);

            if ($result['success']) {
                $wallet->decrement('credits_balance', $creditsRequired);
                $wallet->increment('credits_used_total', $creditsRequired);

                $log->update([
                    'status' => 'sent',
                    'provider_message_id' => $result['messageId'],
                    'sent_at' => now(),
                ]);
            } else {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $result['error'],
                ]);
            }

            return $log;
        });
    }
}
