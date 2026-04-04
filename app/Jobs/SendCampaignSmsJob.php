<?php

namespace App\Jobs;

use Exception;
use Throwable;
use App\Models\Campaign;
use App\Models\SmsWallet;
use Illuminate\Bus\Queueable;
use App\Services\Sms\SmsService;
use App\Models\CampaignRecipient;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendCampaignSmsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public Campaign $campaign,
        public CampaignRecipient $recipient,
    ) {}

    /**
     * Send a single SMS to a campaign recipient.
     */
    public function handle(SmsService $smsService): void
    {
        $customer = $this->recipient->customer;

        if (!$customer) {
            $this->markFailed('Customer not found.');

            return;
        }

        try {
            $log = $smsService->sendToCustomer(
                $customer,
                $this->campaign->message,
                ['triggered_by' => 'campaign', 'campaign_id' => $this->campaign->id]
            );

            if ($log && $log->status === 'sent') {
                $this->recipient->update(['status' => 'sent', 'sent_at' => now()]);
                $this->campaign->increment('recipients_sent');
                $this->campaign->increment('credits_used', (int) $log->credits_used);
            }
            else {
                $this->markFailed($log->error_message ?? 'SMS delivery failed.');
            }
        }
        catch (Exception $e) {
            Log::error("SendCampaignSmsJob failed (Recipient {$this->recipient->id}): {$e->getMessage()}");

            throw $e; // Let the queue retry
        }

        // Check if campaign is complete
        $this->checkCompletion();
    }

    /**
     * Mark the recipient as failed and update campaign counters.
     */
    protected function markFailed(string $reason): void
    {
        $this->recipient->update(['status' => 'failed', 'failed_at' => now()]);
        $this->campaign->increment('recipients_failed');
    }

    /**
     * Check if all recipients have been processed and mark campaign completed.
     */
    protected function checkCompletion(): void
    {
        $campaign = $this->campaign->fresh();

        $processed = $campaign->recipients_sent + $campaign->recipients_failed;

        if ($processed >= $campaign->recipients_total) {
            $campaign->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            // Release reserved credits from the wallet pool
            if ($campaign->credits_reserved > 0) {
                $wallet = SmsWallet::where('tenant_id', $campaign->tenant_id)->first();

                if ($wallet) {
                    $wallet->decrement('credits_reserved', $campaign->credits_reserved);
                }
            }

            Log::info("Campaign [{$campaign->id}] completed: {$campaign->recipients_sent} sent, {$campaign->recipients_failed} failed.");
        }
    }

    /**
     * Handle permanent failure after all retries exhausted.
     */
    public function failed(Throwable $exception): void
    {
        $this->markFailed($exception->getMessage());
        $this->checkCompletion();
    }
}
