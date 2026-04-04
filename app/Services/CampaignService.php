<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\SmsWallet;
use App\Jobs\ProcessCampaignJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class CampaignService
{
    /**
     * Build a query for the target audience based on segment type.
     *
     * @param  array{type: string, days?: int, percentile?: int, location_id?: int}  $segmentConfig
     */
    public function buildAudienceQuery(Tenant $tenant, array $segmentConfig): Builder
    {
        $query = Customer::where('tenant_id', $tenant->id)
            ->where('status', 'active');

        return match ($segmentConfig['type']) {
            'active'     => $query->activeWithin($segmentConfig['days'] ?? 30),
            'lapsed'     => $query->lapsed($segmentConfig['days'] ?? 60),
            'high_value' => $query->highValue($segmentConfig['percentile'] ?? 10),
            'location'   => $query->whereHas('pointTransactions', function ($q) use ($segmentConfig) {
                $q->where('tenant_location_id', $segmentConfig['location_id'])
                    ->where('created_at', '>=', now()->subDays(90));
            }),
            default => $query, // 'all' — every active customer
        };
    }

    /**
     * Get the estimated audience size for a segment.
     */
    public function getAudienceCount(Tenant $tenant, array $segmentConfig): int
    {
        return $this->buildAudienceQuery($tenant, $segmentConfig)->count();
    }

    /**
     * Create and dispatch a campaign atomically.
     *
     * Reserves SMS credits up front. If the wallet balance is insufficient,
     * the campaign is rejected and no records are created.
     *
     * @return array{success: bool, campaign?: Campaign, error?: string}
     */
    public function createAndDispatch(
        Tenant $tenant,
        int $createdByUserId,
        string $name,
        string $message,
        array $segmentConfig,
    ): array {
        $audienceCount = $this->getAudienceCount($tenant, $segmentConfig);
        $creditsRequired = $audienceCount * (int) ceil(strlen($message) / 160);

        if ($audienceCount === 0) {
            return ['success' => false, 'error' => 'No customers match the selected segment.'];
        }

        return DB::transaction(function () use ($tenant, $createdByUserId, $name, $message, $segmentConfig, $audienceCount, $creditsRequired) {
            /** @var SmsWallet $wallet */
            $wallet = SmsWallet::lockForUpdate()->firstOrCreate(
                ['tenant_id' => $tenant->id],
                ['credits_balance' => 0, 'credits_reserved' => 0]
            );

            Log::debug("Campaign Transaction: WalletID={$wallet->id}, Balance={$wallet->credits_balance}, Req={$creditsRequired}");

            if (!$wallet->hasCredits($creditsRequired)) {
                return [
                    'success' => false,
                    'error'   => "Insufficient SMS credits. Need {$creditsRequired}, have {$wallet->credits_balance}.",
                ];
            }

            // Reserve credits
            $wallet->increment('credits_reserved', $creditsRequired);

            $campaign = Campaign::create([
                'tenant_id'          => $tenant->id,
                'created_by_user_id' => $createdByUserId,
                'name'               => $name,
                'message'            => $message,
                'segment_type'       => $segmentConfig['type'],
                'segment_config'     => $segmentConfig,
                'status'             => 'queued',
                'recipients_total'   => $audienceCount,
                'recipients_sent'    => 0,
                'recipients_failed'  => 0,
                'credits_reserved'   => $creditsRequired,
                'credits_used'       => 0,
            ]);

            // Populate recipients
            $this->buildAudienceQuery($tenant, $segmentConfig)
                ->select('id')
                ->chunk(500, function ($customers) use ($campaign) {
                    $rows = $customers->map(fn(Customer $c) => [
                        'campaign_id' => $campaign->id,
                        'customer_id' => $c->id,
                        'status'      => 'pending',
                        'created_at'  => now(),
                    ])->toArray();

                    DB::table('campaign_recipients')->insert($rows);
                });

            ProcessCampaignJob::dispatch($campaign);

            Log::info("Campaign [{$campaign->id}] queued for Tenant [{$tenant->id}]: {$audienceCount} recipients, {$creditsRequired} credits reserved.");

            return ['success' => true, 'campaign' => $campaign];
        });
    }
}
