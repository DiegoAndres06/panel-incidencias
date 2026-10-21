<?php

namespace Tests\Concerns;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Ayudantes para armar historiales de incidencias a mano, saltándose el servicio,
 * que es la única forma de producir datos incoherentes.
 */
trait BuildsIncidentHistory
{
    /**
     * Instante fijo, `$minutes` minutos después de una base conocida.
     */
    protected function at(int $minutes): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-03-10 09:00:00')->addMinutes($minutes);
    }

    protected function incidentWithStatus(IncidentStatus $status, ?CarbonInterface $updatedAt = null): Incident
    {
        return Incident::factory()->withStatus($status)->create([
            'created_at' => $this->at(0),
            'updated_at' => $updatedAt ?? $this->at(600),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    protected function logFor(
        Incident $incident,
        IncidentLogAction $action,
        ?string $old,
        ?string $new,
        CarbonInterface $at,
        ?string $user = 'Ana Torres',
        ?array $metadata = null,
    ): IncidentLog {
        return IncidentLog::factory()->for($incident)->create([
            'action' => $action,
            'old_value' => $old,
            'new_value' => $new,
            'user_name' => $user,
            'metadata' => $metadata,
            'created_at' => $at,
        ]);
    }

    /**
     * Historial sano hasta `$status`: creada y, si procede, en revisión.
     */
    protected function logCreationAndReview(Incident $incident): void
    {
        $this->logFor($incident, IncidentLogAction::Created, null, IncidentStatus::Open->value, $this->at(0));
        $this->logFor($incident, IncidentLogAction::StatusChanged, IncidentStatus::Open->value, IncidentStatus::InReview->value, $this->at(10));
    }
}
