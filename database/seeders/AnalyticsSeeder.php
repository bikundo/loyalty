<?php

namespace Database\Seeders;

use App\Models\Reward;
use App\Models\Tenant;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Redemption;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\PointTransaction;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            return;
        }

        $rewards = Reward::where('tenant_id', $tenant->id)->get();
        if ($rewards->isEmpty()) {
            return;
        }

        // 1. Generate Customers over the last 30 days
        for ($i = 0; $i < 100; $i++) {
            $created = now()->subDays(rand(1, 30));
            $customer = Customer::create([
                'tenant_id'     => $tenant->id,
                'name'          => 'Demo Customer ' . ($i + 1),
                'phone'         => '254700' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT),
                'created_at'    => $created,
                'enrolled_at'   => $created,
                'last_visit_at' => $created->copy()->addDays(rand(1, 5)),
                'total_points'  => 0,
            ]);

            $currentBalance = 0;

            // 2. Generate Transactions for these customers
            $numTransactions = rand(3, 8);
            for ($j = 0; $j < $numTransactions; $j++) {
                $type = rand(0, 5) > 1 ? 'earn' : 'redeem';
                $date = $created->copy()->addDays(rand(0, 30))->min(now());

                if ($type === 'earn') {
                    $points = rand(50, 200);
                    $currentBalance += $points;

                    PointTransaction::create([
                        'tenant_id'        => $tenant->id,
                        'customer_id'      => $customer->id,
                        'type'             => 'earn',
                        'points'           => $points,
                        'balance_after'    => $currentBalance,
                        'amount_spent_kes' => rand(500, 5000),
                        'created_at'       => $date,
                        'idempotency_key'  => Str::uuid()->toString(),
                    ]);
                }
                elseif ($currentBalance >= 100) {
                    $reward = $rewards->random();
                    if ($currentBalance >= $reward->points_required) {
                        $currentBalance -= $reward->points_required;

                        $transaction = PointTransaction::create([
                            'tenant_id'       => $tenant->id,
                            'customer_id'     => $customer->id,
                            'type'            => 'redeem',
                            'points'          => $reward->points_required,
                            'balance_after'   => $currentBalance,
                            'created_at'      => $date,
                            'idempotency_key' => Str::uuid()->toString(),
                        ]);

                        Redemption::create([
                            'tenant_id'            => $tenant->id,
                            'customer_id'          => $customer->id,
                            'reward_id'            => $reward->id,
                            'point_transaction_id' => $transaction->id,
                            'status'               => 'confirmed',
                            'points_used'          => $reward->points_required,
                            'confirmed_at'         => $date,
                        ]);
                    }
                }
            }

            // Sync final balance back to customer
            $customer->total_points = $currentBalance;
            $customer->save();
        }

        // 3. Generate a completed campaign with ROI
        $campaign = Campaign::create([
            'tenant_id'        => $tenant->id,
            'name'             => 'Legacy Re-engagement Bonus',
            'message'          => 'Hey! We miss you. Visit us this weekend for 50 bonus points!',
            'status'           => 'completed',
            'recipients_total' => 50,
            'recipients_sent'  => 50,
            'dispatched_at'    => now()->subDays(10),
            'completed_at'     => now()->subDays(10),
        ]);

        $randomCustomers = Customer::where('tenant_id', $tenant->id)->limit(50)->get();
        foreach ($randomCustomers as $customer) {
            $campaign->recipients()->create([
                'customer_id' => $customer->id,
                'status'      => 'sent',
            ]);

            // Simulate conversion: if they had a transaction AFTER the campaign
            if (rand(0, 10) > 6) {
                $points = 50;
                $customer->increment('total_points', $points);

                PointTransaction::create([
                    'tenant_id'       => $tenant->id,
                    'customer_id'     => $customer->id,
                    'type'            => 'earn',
                    'points'          => $points,
                    'balance_after'   => $customer->total_points,
                    'created_at'      => $campaign->dispatched_at->copy()->addDays(rand(1, 4)),
                    'idempotency_key' => Str::uuid()->toString(),
                ]);
            }
        }
    }
}
