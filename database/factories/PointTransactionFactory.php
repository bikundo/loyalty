<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Customer;
use Illuminate\Support\Str;
use App\Models\PointTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointTransaction>
 */
class PointTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id'        => Tenant::factory(),
            'customer_id'      => Customer::factory(),
            'type'             => 'earn',
            'points'           => $this->faker->numberBetween(10, 500),
            'amount_spent_kes' => $this->faker->numberBetween(50000, 100000), // KES 500-1000
            'balance_after'    => $this->faker->numberBetween(500, 10000), // Mocked cumulative balance
            'note'             => 'Purchase reward',
            'idempotency_key'  => Str::uuid(),
            'created_at'       => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
