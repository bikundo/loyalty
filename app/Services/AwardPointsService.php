<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PointTransaction;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AwardPointsService
{
    public function __construct(
        protected PointsEngine $engine
    ) {}

    /**
     * Handle the awarding process for a specific transaction event.
     *
     * @param Tenant $tenant
     * @param Customer $customer
     * @param array $transactionData e.g., ['amount_spent_kes' => 150]
     * @param array $meta additional tracking e.g., ['location_id' => 1, 'note' => 'via Dashboard']
     * @return array
     */
    public function handle(Tenant $tenant, Customer $customer, array $transactionData, array $meta = []): array
    {
        return DB::transaction(function () use ($tenant, $customer, $transactionData, $meta) {
            $awards = $this->engine->evaluate($tenant, $customer, $transactionData);
            
            if (empty($awards)) {
                return [];
            }

            // Reload the customer inside transaction to get fresh lock if necessary.
            // Ideally should use lockForUpdate if multiple concurrent transactions are likely.
            $customer = Customer::lockForUpdate()->find($customer->id);

            $createdTransactions = [];
            $totalPointsEarned = 0;

            foreach ($awards as $award) {
                $points = $award['points'];
                $rule = $award['rule'];
                
                $totalPointsEarned += $points;

                $afterBalance = $customer->total_points + $totalPointsEarned;

                $createdTransactions[] = PointTransaction::create([
                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'loyalty_rule_id' => $rule->id,
                    'tenant_location_id' => $meta['location_id'] ?? null,
                    'type' => 'earn',
                    'points' => $points,
                    'balance_after' => $afterBalance,
                    'amount_spent_kes' => $transactionData['amount_spent_kes'] ?? null,
                    'note' => $meta['note'] ?? null,
                    'triggered_by' => $meta['triggered_by'] ?? 'system',
                    'triggered_by_user_id' => auth()->id(),
                    'idempotency_key' => Str::uuid()->toString(),
                    'created_at' => now(),
                ]);
            }

            $amountSpent = $transactionData['amount_spent_kes'] ?? 0;
            
            // Update the source of truth customer ledger cache
            $customer->total_points += $totalPointsEarned;
            $customer->lifetime_points_earned += $totalPointsEarned;
            $customer->total_visits += 1;
            $customer->lifetime_spend_kes += $amountSpent;
            $customer->last_visit_at = now();
            $customer->save();

            // TODO: Dispatch SMS Notification / Queue job here
            
            return $createdTransactions;
        });
    }
}
