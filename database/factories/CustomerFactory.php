<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id'              => Tenant::factory(),
            'phone'                  => $this->faker->unique()->e164PhoneNumber(),
            'name'                   => $this->faker->name(),
            'email'                  => $this->faker->safeEmail(),
            'total_points'           => $this->faker->numberBetween(0, 5000),
            'lifetime_points_earned' => $this->faker->numberBetween(5000, 10000),
            'lifetime_spend_kes'     => $this->faker->numberBetween(100000, 1000000), // In cents (KES 1k to 10k)
            'status'                 => 'active',
            'last_visit_at'          => $this->faker->dateTimeBetween('-1 month', 'now'),
            'enrolled_at'            => $this->faker->dateTimeBetween('-1 year', '-1 month'),
        ];
    }
}
