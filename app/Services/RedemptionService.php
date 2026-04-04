<?php

namespace App\Services;

use Exception;
use App\Models\Reward;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Redemption;
use Illuminate\Support\Str;
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
     * Handle the atomic point-based redemption of a reward.
     */
    public function handle(Tenant $tenant, Customer $customer, Reward $reward, array $meta = []): Redemption
    {
        return DB::transaction(function () use ($tenant, $customer, $reward, $meta) {
            // Re-fetch customer with lock for safe points decrement
            $customer = Customer::lockForUpdate()->find($customer->id);

            if ($customer->total_points < $reward->points_required) {
                throw new Exception("Insufficient points. Customer has {$customer->total_points} total points, but {$reward->name} requires {$reward->points_required}.");
            }

            // 0. FIFO Points Depletion (for Expiry tracking)
            $pointsToDeplete = $reward->points_required;
            $earnTransactions = PointTransaction::where('customer_id', $customer->id)
                ->where('type', 'earn')
                ->where('points_remaining', '>', 0)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            foreach ($earnTransactions as $earnTx) {
                if ($pointsToDeplete <= 0) {
                    break;
                }

                $usage = min($earnTx->points_remaining, $pointsToDeplete);
                $earnTx->decrement('points_remaining', $usage);
                $pointsToDeplete -= $usage;
            }

            // 1. Create Point Transaction (Redeem type)
            // Note: HasUuid trait handles the 'uuid' field via the 'creating' event.
            $transaction = PointTransaction::create([
                'tenant_id'            => $tenant->id,
                'customer_id'          => $customer->id,
                'tenant_location_id'   => $meta['location_id'] ?? null,
                'type'                 => 'redeem',
                'points'               => $reward->points_required,
                'points_remaining'     => -$reward->points_required, // Audit: negative for redemptions
                'balance_after'        => $customer->total_points - $reward->points_required,
                'note'                 => $meta['note'] ?? "Redeemed for: {$reward->name}",
                'triggered_by'         => $meta['triggered_by'] ?? 'merchant_portal',
                'triggered_by_user_id' => $meta['user_id'] ?? Auth::id(),
                'idempotency_key'      => Str::uuid()->toString(),
                'created_at'           => now(),
            ]);

            // 2. Create Redemption Record
            $redemption = Redemption::create([
                'tenant_id'            => $tenant->id,
                'customer_id'          => $customer->id,
                'reward_id'            => $reward->id,
                'tenant_location_id'   => $meta['location_id'] ?? null,
                'point_transaction_id' => $transaction->id,
                'status'               => 'confirmed',
                'points_used'          => $reward->points_required,
                'confirmed_at'         => now(),
                'confirmed_by_user_id' => $meta['user_id'] ?? Auth::id(),
            ]);

            // 3. Update Customer Balance
            $customer->total_points -= $reward->points_required;
            $customer->total_visits += 1;
            $customer->last_visit_at = now();
            $customer->save();

            // 4. Update Reward Stats
            $reward->increment('redemptions_count');

            // 5. Send Confirmation SMS
            $this->smsService->sendToCustomer(
                $customer,
                "Redemption Successful! You've redeemed {$reward->name} for {$reward->points_required} points. Your new balance is {$customer->total_points}.",
                ['triggered_by' => 'redemption_service']
            );

            return $redemption;
        });
    }
}
