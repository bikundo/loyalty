<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name'               => $name,
            'slug'               => Str::slug($name),
            'subdomain'          => Str::slug($name),
            'plan_id'            => Plan::factory(),
            'status'             => 'active',
            'preferred_currency' => 'KES',
            'timezone'           => 'Africa/Nairobi',
            'country_code'       => 'KE',
        ];
    }
}
