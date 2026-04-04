<?php

namespace Database\Factories;

use App\Models\LoyaltyRule;
use App\Models\Tenant;
use App\Models\LoyaltyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyRule>
 */
class LoyaltyRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'loyalty_program_id' => LoyaltyProgram::factory(),
            'name' => $this->faker->words(3, true),
            'type' => 'spend',
            'config' => [
                'min_spend_kes' => 0,
                'points_per_kes' => 1,
            ],
            'is_active' => true,
            'stack_with_others' => true,
            'priority' => 0,
        ];
    }
}
