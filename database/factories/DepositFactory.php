<?php

namespace Database\Factories;

use App\Models\Deposit;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'goal_id' => Goal::factory(),
            'amount' => fake()->numberBetween(10, 500),
            'note' => fake()->optional()->sentence(4),
        ];
    }
}
