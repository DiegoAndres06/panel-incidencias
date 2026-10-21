<?php

namespace Database\Factories;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'description' => fake()->optional()->paragraph(),
            'status' => IncidentStatus::Open,
            'priority' => fake()->randomElement(IncidentPriority::cases()),
            'requester_name' => fake()->name(),
            'assigned_to' => fake()->optional()->name(),
        ];
    }

    /**
     * Fija el estado de la incidencia.
     */
    public function withStatus(IncidentStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
