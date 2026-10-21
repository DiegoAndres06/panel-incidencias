<?php

namespace Database\Seeders;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

/**
 * Tres incidencias con datos incoherentes a propósito, para practicar el diagnóstico
 * (`php artisan incidents:diagnose`). Se escriben directamente en las tablas, saltándose
 * `IncidentService`, que es justo lo que impide estas situaciones en la aplicación. Los
 * datos son ficticios.
 *
 * 1. Resuelta sin log de resolución: el último log es el paso a «En revisión».
 * 2. Estado distinto del último log: la fila dice «Bloqueada», el último log «En revisión».
 * 3. Log duplicado: el paso a «En revisión» está registrado dos veces, idéntico.
 */
class InconsistentIncidentsSeeder extends Seeder
{
    public const RESOLVED_WITHOUT_LOG_TITLE = 'Falla de sincronización con el ERP';

    public const STATUS_MISMATCH_TITLE = 'Alertas de disco lleno en el servidor de archivos';

    public const DUPLICATE_LOGS_TITLE = 'Cambios de turno sin reflejar en el calendario';

    public function run(): void
    {
        $now = Date::now();

        $this->resolvedWithoutResolutionLog($now);
        $this->statusMismatchingLastLog($now);
        $this->duplicateLogs($now);
    }

    private function resolvedWithoutResolutionLog(CarbonInterface $now): void
    {
        $incident = $this->incident(self::RESOLVED_WITHOUT_LOG_TITLE, IncidentStatus::Resolved, IncidentPriority::High, 'Raúl Benítez', $now->subDays(15), $now->subDays(13));

        $this->log($incident, IncidentLogAction::Created, null, IncidentStatus::Open, 'Raúl Benítez', $now->subDays(15));
        $this->log($incident, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Laura Gómez', $now->subDays(14));
        // Falta el log de la resolución.
    }

    private function statusMismatchingLastLog(CarbonInterface $now): void
    {
        $incident = $this->incident(self::STATUS_MISMATCH_TITLE, IncidentStatus::Blocked, IncidentPriority::Medium, 'Nuria Pastor', $now->subDays(10), $now->subDays(8));

        $this->log($incident, IncidentLogAction::Created, null, IncidentStatus::Open, 'Nuria Pastor', $now->subDays(10));
        $this->log($incident, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Carlos Peña', $now->subDays(9));
        // La fila pasó a «Bloqueada» sin que se registrara el cambio.
    }

    private function duplicateLogs(CarbonInterface $now): void
    {
        $incident = $this->incident(self::DUPLICATE_LOGS_TITLE, IncidentStatus::InReview, IncidentPriority::Low, 'Víctor Lozano', $now->subDays(6), $now->subDays(5));

        $this->log($incident, IncidentLogAction::Created, null, IncidentStatus::Open, 'Víctor Lozano', $now->subDays(6));
        $this->log($incident, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Laura Gómez', $now->subDays(5));
        $this->log($incident, IncidentLogAction::StatusChanged, IncidentStatus::Open, IncidentStatus::InReview, 'Laura Gómez', $now->subDays(5));
    }

    private function incident(string $title, IncidentStatus $status, IncidentPriority $priority, string $requester, CarbonInterface $createdAt, CarbonInterface $updatedAt): Incident
    {
        return Incident::factory()->create([
            'title' => $title,
            'description' => null,
            'status' => $status,
            'priority' => $priority,
            'requester_name' => $requester,
            'assigned_to' => null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
    }

    private function log(Incident $incident, IncidentLogAction $action, ?IncidentStatus $old, IncidentStatus $new, string $actor, CarbonInterface $at): void
    {
        IncidentLog::factory()->for($incident)->create([
            'action' => $action,
            'old_value' => $old?->value,
            'new_value' => $new->value,
            'user_name' => $actor,
            'metadata' => null,
            'created_at' => $at,
        ]);
    }
}
