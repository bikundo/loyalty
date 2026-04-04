<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Reward;
use App\Models\Redemption;
use App\Models\PointTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RedemptionService
{
    /**
     * Process a redemption for a customer and a reward.
     *
     * @param Customer $customer
     * @param Reward $reward
     * @param int|null $userId
     * @param int|null $cashierId
     * @param array $meta
     * @return Redemption
     * @throws InvalidArgumentException
     */
    public function redeem(Customer $customer, Reward $reward, ?int $userId = null, ?int $cashierId = null, array $meta = []): Redemption
    {
        if ($customer->total_points < $reward->points_required) {
            throw new InvalidArgumentException('Insufficient points');
        }

        return DB::transaction(function () use ($customer, $reward, $userId, $cashierId, $meta) {
            // Re-fetch customer with lock for safe point deduction
            $customer = Customer::lockForUpdate()->find($customer->id);
            
            if ($customer->total_points < $reward->points_required) {
                throw new InvalidArgumentException('Insufficient points (concurrency lock)');
            }

            $pointsUsed = $reward->points_required;
            $newBalance = $customer->total_points - $pointsUsed;

            // 1. Create Point Transaction Ledger Entry (Debit)
            $transaction = PointTransaction::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'type' => 'redeem',
                'points' => -$pointsUsed,
                'balance_after' => $newBalance,
                'cashier_id' => $cashierId,
                'triggered_by_user_id' => $userId ?? Auth::id(),
                'idempotency_key' => Str::uuid()->toString(),
                'note' => $meta['note'] ?? "Redemption: {$reward->name}",
                'created_at' => now(),
            ]);

            // 2. Create Redemption Record
            $redemption = Redemption::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'reward_id' => $reward->id,
                'status' => 'confirmed',
                'points_used' => $pointsUsed,
                'point_transaction_id' => $transaction->id,
                'initiated_by_cashier_id' => $cashierId,
                'confirmed_by_cashier_id' => $cashierId,
                'confirmed_by_user_id' => $userId,
                'confirmed_at' => now(),
            ]);

            // 3. Update Customer Balance
            $customer->total_points = $newBalance;
            $customer->save();

            // 4. Update Reward Stats
            $reward = Reward::lockForUpdate()->find($reward->id);
            $reward->increment('redemptions_count');

            return $redemption;
        });
    }
}
