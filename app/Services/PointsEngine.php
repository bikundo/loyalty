<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyRule;
use App\Models\Tenant;

class PointsEngine
{
    /**
     * Evaluate rules for a given transaction to determine points to award.
     *
     * @param Tenant $tenant
     * @param Customer $customer
     * @param array $transactionData e.g., ['amount_spent_kes' => 1500]
     * @return array<int, array{rule: LoyaltyRule, points: int}>
     */
    public function evaluate(Tenant $tenant, Customer $customer, array $transactionData): array
    {
        $rules = LoyaltyRule::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', today());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            })
            ->orderBy('priority', 'asc')
            ->get();

        $awards = [];

        /** @var \App\Models\LoyaltyRule $rule */
        foreach ($rules as $rule) {
            // Cannot stack if we've already generated an award
            if (!$rule->stack_with_others && count($awards) > 0) {
                continue;
            }

            $points = $this->calculatePointsForRule($rule, $transactionData);

            if ($points > 0) {
                $awards[] = [
                    'rule' => $rule,
                    'points' => $points,
                ];

                // If this rule cannot stack with others, we stop evaluating further rules.
                if (!$rule->stack_with_others) {
                    break;
                }
            }
        }

        return $awards;
    }

    private function calculatePointsForRule(LoyaltyRule $rule, array $transactionData): int
    {
        $config = $rule->config ?? [];

        switch ($rule->type) {
            case 'visit':
                return (int) ($config['points_awarded'] ?? 1);

            case 'spend':
                $amountSpent = $transactionData['amount_spent_kes'] ?? 0;
                $minSpend = $config['min_spend_kes'] ?? 0;

                if ($amountSpent < $minSpend) {
                    return 0;
                }

                $pointsPerKes = $config['points_per_kes'] ?? 0;
                return (int) round($amountSpent * $pointsPerKes);

            default:
                return 0;
        }
    }
}
