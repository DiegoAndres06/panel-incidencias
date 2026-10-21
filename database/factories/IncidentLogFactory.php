<?php

namespace Database\Factories;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentLog>
 */
class IncidentLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'action' => IncidentLogAction::StatusChanged,
            'old_value' => IncidentStatus::Open->value,
            'new_value' => IncidentStatus::InReview->value,
            'user_name' => fake()->name(),
            'metadata' => null,
        ];
    }
}
