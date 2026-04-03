<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'                       => 'Starter',
                'slug'                       => 'starter',
                'price_amount'               => 500000, // 5,000 KES
                'max_locations'              => 1,
                'max_cashiers'               => 3,
                'sms_wallet_topup_bonus_pct' => 0,
                'api_access_enabled'         => false,
                'ussd_enabled'               => true,
                'coalition_enabled'          => false,
                'branded_app_enabled'        => false,
                'is_active'                  => true,
                'sort_order'                 => 1,
                'features'                   => ['1 location included', '3 cashiers included', 'USSD collection support'],
            ],
            [
                'name'                       => 'Growth',
                'slug'                       => 'growth',
                'price_amount'               => 1500000, // 15,000 KES
                'max_locations'              => 5,
                'max_cashiers'               => 20,
                'sms_wallet_topup_bonus_pct' => 5,
                'api_access_enabled'         => true,
                'ussd_enabled'               => true,
                'coalition_enabled'          => false,
                'branded_app_enabled'        => false,
                'is_active'                  => true,
                'sort_order'                 => 2,
                'features'                   => ['5 locations included', '20 cashiers included', 'API access', '5% SMS top-up bonus'],
            ],
            [
                'name'                       => 'Business',
                'slug'                       => 'business',
                'price_amount'               => 5000000, // 50,000 KES
                'max_locations'              => 25,
                'max_cashiers'               => 100,
                'sms_wallet_topup_bonus_pct' => 10,
                'api_access_enabled'         => true,
                'ussd_enabled'               => true,
                'coalition_enabled'          => true,
                'branded_app_enabled'        => true,
                'is_active'                  => true,
                'sort_order'                 => 3,
                'features'                   => ['25 locations included', '100 cashiers included', 'Branded app supported', 'Coalition-ready', '10% SMS bonus'],
            ],
            [
                'name'                       => 'Enterprise',
                'slug'                       => 'enterprise',
                'price_amount'               => 0, // Custom quotiing
                'max_locations'              => 999,
                'max_cashiers'               => 999,
                'sms_wallet_topup_bonus_pct' => 15,
                'api_access_enabled'         => true,
                'ussd_enabled'               => true,
                'coalition_enabled'          => true,
                'branded_app_enabled'        => true,
                'is_active'                  => true,
                'sort_order'                 => 4,
                'features'                   => ['Unlimited locations/cashiers', 'Priority support', 'Dedicated account manager', 'Max SMS bonuses'],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::create($planData);
        }
    }
}
