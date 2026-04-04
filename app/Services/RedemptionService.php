<?php

namespace App\Services;

use App\Models\Reward;
use App\Models\Customer;
use App\Models\Redemption;
use Illuminate\Support\Str;
use InvalidArgumentException;
use App\Models\PointTransaction;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RedemptionService
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * Process a redemption for a customer and a reward.
     */
    public function redeem(Customer $customer, Reward $reward, ?int $userId = null, ?int $cashierId = null, array $meta = []): Redemption
    {
        if ($reward->tenant_id !== $customer->tenant_id) {
            throw new InvalidArgumentException('Unauthorized reward selection for this customer');
        }

        if ($customer->total_points < $reward->points_required) {
            throw new InvalidArgumentException('Insufficient points');
        }

        return DB::transaction(function () use ($customer, $reward, $userId, $cashierId, $meta) {
            // 1. Lock customer for safe point deduction
            $customer = Customer::lockForUpdate()->find($customer->id);

            $pointsBefore = $customer->total_points;
            $pointsRequired = $reward->points_required;
            $newBalance = $pointsBefore - $pointsRequired;

            // 2. Create Point Transaction Ledger Entry (Debit)
            PointTransaction::create([
                'tenant_id'            => $customer->tenant_id,
                'customer_id'          => $customer->id,
                'tenant_location_id'   => $meta['tenant_location_id'] ?? null,
                'type'                 => 'redemption',
                'points'               => -$pointsRequired,
                'balance_after'        => $newBalance,
                'triggered_by_user_id' => $userId ?? Auth::id(),
                'triggered_by'         => $cashierId ? 'cashier' : 'merchant',
                'idempotency_key'      => Str::uuid()->toString(),
                'note'                 => $meta['note'] ?? "Redemption: {$reward->name}",
                'created_at'           => now(),
            ]);

            // 3. Create Redemption Record
            $redemption = Redemption::create([
                'tenant_id'               => $customer->tenant_id,
                'customer_id'             => $customer->id,
                'reward_id'               => $reward->id,
                'tenant_location_id'      => $meta['tenant_location_id'] ?? null,
                'status'                  => 'confirmed',
                'points_used'             => $pointsRequired,
                'initiated_by_cashier_id' => $cashierId,
                'confirmed_by_cashier_id' => $cashierId,
                'confirmed_by_user_id'    => $userId ?? Auth::id(),
                'confirmed_at'            => now(),
            ]);

            // 4. Update Customer Ledger Stats
            $customer->total_points = $newBalance;
            $customer->lifetime_points_redeemed += $pointsRequired;
            $customer->save();

            // 5. Update Reward Stats
            $reward->increment('redemptions_count');

            // 6. Dispatch SMS Notification
            $this->smsService->sendToCustomer(
                $customer,
                "Redemption Success! You've redeemed {$reward->name} at {$customer->tenant->name}. New Balance: {$customer->total_points}.",
                ['triggered_by' => 'redemption_service']
            );

            return $redemption;
        });
    }
}
