<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\LoyaltyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyProgram>
 */
class LoyaltyProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id'           => Tenant::factory(),
            'name'                => 'Full Rewards',
            'points_to_kes_ratio' => 100, // 100 points = 1 KES
            'expiry_days'         => 365,
            'is_active'           => true,
        ];
    }
}
