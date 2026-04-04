<?php

namespace App\Services;

use Exception;
use App\Models\Tenant;
use App\Models\Cashier;
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
            $amountSpent = (float) ($transactionData['amount_spent_kes'] ?? 0);
            $userId = $meta['user_id'] ?? Auth::id();

            // Guard: Daily Award Cap for Cashiers (Fraud Prevention)
            if ($userId) {
                $cashier = Cashier::where('user_id', $userId)
                    ->where('tenant_id', $tenant->id)
                    ->lockForUpdate()
                    ->first();

                if ($cashier && $cashier->daily_award_cap_kes > 0) {
                    if ($cashier->total_awarded_today_kes + $amountSpent > $cashier->daily_award_cap_kes) {
                        throw new Exception("Daily award limit exceeded for this cashier (Limit: KES {$cashier->daily_award_cap_kes}).");
                    }

                    $cashier->increment('total_awarded_today_kes', $amountSpent);
                }
            }

            // Re-fetch customer with lock for safe points increment
            $customer = Customer::lockForUpdate()->find($customer->id);

            $awards = $this->engine->evaluate($tenant, $customer, $transactionData);

            if (empty($awards)) {
                return [];
            }

            $createdTransactions = [];
            $totalPointsEarned = 0;

            $loyaltyProgram = $tenant->loyaltyProgram;
            $expiryDays = $loyaltyProgram->expiry_days;
            $expiresAt = $expiryDays > 0 ? now()->addDays($expiryDays) : null;

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
                    'points_remaining'     => $points,
                    'balance_after'        => $afterBalance,
                    'amount_spent_kes'     => $transactionData['amount_spent_kes'] ?? null,
                    'note'                 => $meta['note'] ?? "Points earned via {$rule->name}",
                    'triggered_by'         => $meta['triggered_by'] ?? 'system',
                    'triggered_by_user_id' => $meta['user_id'] ?? Auth::id(),
                    'idempotency_key'      => Str::uuid()->toString(),
                    'expires_at'           => $expiresAt,
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

            // Hook for Milestone Automations
            app(AutomationService::class)->evaluateMilestones($customer);

            // Hook for Referral Qualification (First Visit)
            if ($customer->total_visits === 1) {
                app(ReferralService::class)->qualify($customer);
            }

            return $createdTransactions;
        });
    }

    /**
     * Award points manually or via automation without rule evaluation.
     */
    public function awardBonus(Tenant $tenant, Customer $customer, int $points, string $note, array $meta = []): PointTransaction
    {
        return DB::transaction(function () use ($tenant, $customer, $points, $note, $meta) {
            $customer = Customer::lockForUpdate()->find($customer->id);

            $loyaltyProgram = $tenant->loyaltyProgram;
            $expiryDays = $loyaltyProgram->expiry_days;
            $expiresAt = $expiryDays > 0 ? now()->addDays($expiryDays) : null;

            $transaction = PointTransaction::create([
                'tenant_id'            => $tenant->id,
                'customer_id'          => $customer->id,
                'tenant_location_id'   => $meta['location_id'] ?? null,
                'type'                 => 'earn',
                'points'               => $points,
                'points_remaining'     => $points,
                'balance_after'        => $customer->total_points + $points,
                'note'                 => $note,
                'triggered_by'         => $meta['triggered_by'] ?? 'system',
                'triggered_by_user_id' => $meta['user_id'] ?? null,
                'idempotency_key'      => Str::uuid()->toString(),
                'expires_at'           => $expiresAt,
                'created_at'           => now(),
            ]);

            $customer->total_points += $points;
            $customer->lifetime_points_earned += $points;
            $customer->save();

            // Hook for Milestone Automations
            app(AutomationService::class)->evaluateMilestones($customer);

            // Hook for Referral Qualification (First Visit)
            if ($customer->total_visits === 1) {
                app(ReferralService::class)->qualify($customer);
            }

            return $transaction;
        });
    }
}
