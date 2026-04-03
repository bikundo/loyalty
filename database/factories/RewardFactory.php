<?php

namespace Database\Factories;

use App\Models\Reward;
use App\Models\Tenant;
use App\Models\LoyaltyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reward>
 */
class RewardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id'             => Tenant::factory(),
            'loyalty_program_id'    => LoyaltyProgram::factory(),
            'name'                  => $this->faker->words(3, true),
            'description'           => $this->faker->sentence(),
            'points_required'       => $this->faker->numberBetween(100, 2000),
            'is_active'             => true,
            'image_url'             => 'https://via.placeholder.com/400x300',
            'max_redemptions_total' => $this->faker->numberBetween(10, 100),
            'type'                  => 'discount',
            'redemptions_count'     => $this->faker->numberBetween(0, 50),
        ];
    }
}
