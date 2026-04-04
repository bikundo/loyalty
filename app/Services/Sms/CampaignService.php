<?php

namespace App\Services\Sms;

use App\Models\Tenant;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Customer;
use App\Models\SmsWallet;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CampaignService
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Create a campaign and prepare recipients.
     *
     * @param Tenant $tenant
     * @param array $data {name: string, message: string, segment_type: string}
     * @return Campaign
     */
    public function create(Tenant $tenant, array $data): Campaign
    {
        return DB::transaction(function () use ($tenant, $data) {
            // 1. Resolve recipients based on segment
            $recipientsQuery = $this->getSegmentQuery($tenant, $data['segment_type'] ?? 'all');
            $recipientIds = $recipientsQuery->pluck('id');
            $totalRecipients = $recipientIds->count();

            // 2. Reserve credits in wallet
            /** @var SmsWallet $wallet */
            $wallet = SmsWallet::lockForUpdate()->firstOrCreate(['tenant_id' => $tenant->id]);
            $creditsRequired = (int) ceil(strlen($data['message']) / 160) * $totalRecipients;

            // Notice: We don't block here, but we record it in the campaign status
            $status = $wallet->hasCredits($creditsRequired) ? 'pending' : 'insufficient_funds';

            // 3. Create Campaign
            $campaign = Campaign::create([
                'tenant_id' => $tenant->id,
                'created_by_user_id' => Auth::id(),
                'name' => $data['name'],
                'message' => $data['message'],
                'segment_type' => $data['segment_type'] ?? 'all',
                'status' => $status,
                'recipients_total' => $totalRecipients,
                'credits_reserved' => $creditsRequired,
                'uuid' => Str::uuid()->toString(),
            ]);

            // 4. Batch insert recipients
            $recipientRecords = $recipientIds->map(fn($id) => [
                'campaign_id' => $campaign->id,
                'customer_id' => $id,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            foreach (array_chunk($recipientRecords, 500) as $chunk) {
                CampaignRecipient::insert($chunk);
            }

            return $campaign;
        });
    }

    /**
     * Resolve customer segment query.
     */
    protected function getSegmentQuery(Tenant $tenant, string $segmentType)
    {
        $query = Customer::where('tenant_id', $tenant->id);

        return match ($segmentType) {
            'active_30' => $query->where('last_visit_at', '>=', now()->subDays(30)),
            'churning' => $query->where('last_visit_at', '<=', now()->subDays(60)),
            default => $query,
        };
    }
}
