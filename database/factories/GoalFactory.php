<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement([
                'New Laptop',
                'Emergency Fund',
                'Vacation',
                'Mechanical Keyboard',
                'Conference Trip',
                'Home Office Setup',
            ]),
            'target' => fake()->numberBetween(200, 5000),
            'deadline' => fake()->optional()->dateTimeBetween('+1 month', '+1 year')?->format('Y-m-d'),
        ];
    }

    public function withoutDeadline(): self
    {
        return $this->state(['deadline' => null]);
    }
}
