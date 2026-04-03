<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name'                       => ucfirst($name),
            'slug'                       => Str::slug($name),
            'price_amount'               => $this->faker->numberBetween(1000, 100000),
            'currency'                   => 'KES',
            'billing_interval'           => 'monthly',
            'sms_wallet_topup_bonus_pct' => $this->faker->numberBetween(0, 20),
            'max_locations'              => $this->faker->numberBetween(1, 10),
            'max_cashiers'               => $this->faker->numberBetween(1, 50),
            'api_access_enabled'         => $this->faker->boolean(),
            'ussd_enabled'               => $this->faker->boolean(),
            'coalition_enabled'          => $this->faker->boolean(),
            'branded_app_enabled'        => $this->faker->boolean(),
            'rate_limit_per_day'         => $this->faker->numberBetween(100, 1000),
            'features'                   => ['Feature A', 'Feature B'],
            'is_active'                  => true,
            'sort_order'                 => $this->faker->numberBetween(1, 10),
        ];
    }
}
