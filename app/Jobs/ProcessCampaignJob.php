<?php

namespace App\Jobs;

use Exception;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use App\Services\Sms\SmsService;
use App\Models\CampaignRecipient;
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

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        // 1. Mark campaign as processing
        $this->campaign->update(['status' => 'processing', 'dispatched_at' => now()]);

        // 2. Loop through pending recipients in chunks
        $this->campaign->recipients()
            ->where('status', 'pending')
            ->chunkById(100, function ($recipients) use ($smsService) {
                /** @var CampaignRecipient[] $recipients */
                foreach ($recipients as $recipient) {
                    try {
                        $log = $smsService->sendToCustomer(
                            $recipient->customer,
                            $this->campaign->message,
                            ['triggered_by' => 'campaign', 'campaign_id' => $this->campaign->id]
                        );

                        if ($log && $log->status === 'sent') {
                            $recipient->update(['status' => 'sent', 'sent_at' => now()]);
                            $this->campaign->increment('recipients_sent');
                        }
                        else {
                            $errorMessage = $log->error_message ?? 'Failed to dispatch SMS';
                            $recipient->update(['status' => 'failed', 'error_message' => $errorMessage]);
                            $this->campaign->increment('recipients_failed');
                        }
                    }
                    catch (Exception $e) {
                        Log::error("Campaign Dispatch Error (Recipient: {$recipient->id}): " . $e->getMessage());
                        $recipient->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                        $this->campaign->increment('recipients_failed');
                    }
                }
            });

        // 3. Mark campaign as completed
        $this->campaign->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
    }
}
