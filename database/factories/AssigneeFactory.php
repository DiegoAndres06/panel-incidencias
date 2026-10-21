<?php

namespace Database\Factories;

use App\Models\Assignee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignee>
 */
class AssigneeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
        ];
    }
}
