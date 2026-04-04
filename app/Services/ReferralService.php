<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerReferral;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    public function __construct(
        protected AwardPointsService $awardPointsService,
        protected SmsService $smsService
    ) {}

    /**
     * Link a new referee to a referrer via a code.
     */
    public function link(Customer $referee, string $referralCode): bool
    {
        $referrer = Customer::where('tenant_id', $referee->tenant_id)
            ->where('referral_code', strtoupper($referralCode))
            ->first();

        if (!$referrer || $referrer->id === $referee->id) {
            return false;
        }

        // Check if referee is already linked
        if ($referee->referred_by_customer_id) {
            return false;
        }

        return DB::transaction(function () use ($referrer, $referee) {
            $referee->update(['referred_by_customer_id' => $referrer->id]);

            CustomerReferral::create([
                'tenant_id'            => $referee->tenant_id,
                'referrer_customer_id' => $referrer->id,
                'referred_customer_id' => $referee->id,
                'status'               => 'pending',
            ]);

            return true;
        });
    }

    /**
     * Qualify the referral (called on referee's first visit).
     */
    public function qualify(Customer $referee): void
    {
        Log::info("Qualifying referee: {$referee->id}, total_visits: {$referee->total_visits}");

        $referral = CustomerReferral::where('referred_customer_id', $referee->id)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            Log::warning("No pending referral found for referee {$referee->id}");

            return;
        }

        $tenant = $referee->tenant;
        $rewardPoints = $tenant->loyaltyProgram->referral_reward_points ?? 0;

        Log::info("Referral reward points for tenant {$tenant->id}: {$rewardPoints}");

        if ($rewardPoints <= 0) {
            return;
        }

        DB::transaction(function () use ($referral, $referee, $tenant, $rewardPoints) {
            $referral->update([
                'status'       => 'qualified',
                'qualified_at' => now(),
            ]);

            // Award points to the referrer
            $referrer = $referral->referrer;

            $this->awardPointsService->awardBonus(
                $tenant,
                $referrer,
                $rewardPoints,
                "Referral Reward: Invited {$referee->name}"
            );

            $referral->update(['credited_at' => now()]);

            // Notify Referrer
            $this->smsService->sendToCustomer(
                $referrer,
                "Great News! Your friend {$referee->name} just joined {$tenant->name}. We've credited your account with $rewardPoints referral points!",
                ['trigger' => 'referral_reward']
            );
        });
    }
}
