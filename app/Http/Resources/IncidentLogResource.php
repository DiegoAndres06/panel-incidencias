<?php

namespace App\Http\Resources;

use App\Models\IncidentLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma en que un registro de trazabilidad llega al frontend.
 *
 * @mixin IncidentLog
 */
class IncidentLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'incident_id' => $this->incident_id,
            'action' => $this->action->value,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'user_name' => $this->user_name,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
