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
    public function redeem(Customer $customer, Reward $reward, ?int $userId = null, ?int $cashierId = null, array $meta = []): Redemption
    {
        if ($reward->tenant_id !== $customer->tenant_id) {
            throw new InvalidArgumentException('Unauthorized reward selection for this customer');
        }

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
            $locationId = $meta['tenant_location_id'] ?? null;

            // 1. Create Point Transaction Ledger Entry (Debit)
            $transaction = PointTransaction::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'tenant_location_id' => $locationId,
                'type' => 'redeem',
                'points' => -$pointsUsed,
                'balance_after' => $newBalance,
                'cashier_id' => $cashierId,
                'triggered_by_user_id' => $userId ?? Auth::id(),
                'triggered_by' => $cashierId ? 'cashier' : 'merchant',
                'idempotency_key' => Str::uuid()->toString(),
                'note' => $meta['note'] ?? "Redemption: {$reward->name}",
                'created_at' => now(),
            ]);

            // 2. Create Redemption Record
            $redemption = Redemption::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'reward_id' => $reward->id,
                'tenant_location_id' => $locationId,
                'status' => 'confirmed',
                'points_used' => $pointsUsed,
                'point_transaction_id' => $transaction->id,
                'initiated_by_cashier_id' => $cashierId,
                'confirmed_by_cashier_id' => $cashierId,
                'confirmed_by_user_id' => $userId ?? Auth::id(),
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
