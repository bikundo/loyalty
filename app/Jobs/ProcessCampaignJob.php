<?php

namespace App\Jobs;

use Throwable;
use App\Models\Campaign;
use App\Models\SmsWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Campaign $campaign
    ) {}

    /**
     * Chunk pending recipients and dispatch individual SMS jobs.
     */
    public function handle(): void
    {
        $this->campaign->update(['status' => 'processing', 'dispatched_at' => now()]);

        $this->campaign->recipients()
            ->where('status', 'pending')
            ->with('customer')
            ->chunkById(100, function ($recipients) {
                foreach ($recipients as $recipient) {
                    SendCampaignSmsJob::dispatch($this->campaign, $recipient);
                }
            });

        Log::info("Campaign [{$this->campaign->id}]: dispatched SMS jobs for {$this->campaign->recipients_total} recipients.");
    }

    /**
     * Release reserved credits if the job fails permanently.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("Campaign [{$this->campaign->id}] failed: {$exception->getMessage()}");

        $this->campaign->update(['status' => 'failed']);

        // Release reserved credits back to the wallet
        $wallet = SmsWallet::where('tenant_id', $this->campaign->tenant_id)->first();

        if ($wallet && $this->campaign->credits_reserved > 0) {
            $wallet->decrement('credits_reserved', $this->campaign->credits_reserved);
        }
    }
}
