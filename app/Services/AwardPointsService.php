<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Customer;
use Illuminate\Support\Str;
use App\Models\PointTransaction;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AwardPointsService
{
    public function __construct(
        protected PointsEngine $engine,
        protected SmsService $smsService
    ) {}

    public function handle(Tenant $tenant, Customer $customer, array $transactionData, array $meta = []): array
    {
        return DB::transaction(function () use ($tenant, $customer, $transactionData, $meta) {
            // Re-fetch customer with lock for safe points increment
            $customer = Customer::lockForUpdate()->find($customer->id);

            $awards = $this->engine->evaluate($tenant, $customer, $transactionData);

            if (empty($awards)) {
                return [];
            }

            $createdTransactions = [];
            $totalPointsEarned = 0;

            foreach ($awards as $award) {
                $points = $award['points'];
                $rule = $award['rule'];

                $totalPointsEarned += $points;

                $afterBalance = $customer->total_points + $totalPointsEarned;

                $createdTransactions[] = PointTransaction::create([
                    'tenant_id'            => $tenant->id,
                    'customer_id'          => $customer->id,
                    'loyalty_rule_id'      => $rule->id,
                    'tenant_location_id'   => $meta['location_id'] ?? null,
                    'type'                 => 'earn',
                    'points'               => $points,
                    'balance_after'        => $afterBalance,
                    'amount_spent_kes'     => $transactionData['amount_spent_kes'] ?? null,
                    'note'                 => $meta['note'] ?? "Points earned via {$rule->name}",
                    'triggered_by'         => $meta['triggered_by'] ?? 'system',
                    'triggered_by_user_id' => $meta['user_id'] ?? Auth::id(),
                    'idempotency_key'      => Str::uuid()->toString(),
                    'created_at'           => now(),
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

            $this->smsService->sendToCustomer(
                $customer,
                "Earning Confirmed! You've earned $totalPointsEarned points at {$tenant->name}. New Balance: {$customer->total_points}.",
                ['triggered_by' => 'awarding_service']
            );

            return $createdTransactions;
        });
    }
}
