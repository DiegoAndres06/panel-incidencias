<?php

namespace App\Http\Resources;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma en que una incidencia llega al frontend.
 *
 * @mixin Incident
 */
class IncidentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'requester_name' => $this->requester_name,
            'assigned_to' => $this->assigned_to,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            // El servidor es quien decide a qué estados se puede pasar desde el actual.
            'allowed_transitions' => array_map(
                fn (IncidentStatus $status) => $status->value,
                $this->status->allowedTransitions(),
            ),
        ];
    }
}
