<?php

namespace App\Services\Diagnosis\Checks;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use App\Services\Diagnosis\Finding;
use App\Services\Diagnosis\InconsistencyCheck;
use App\Services\Diagnosis\Resolution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Incidencias cuyo estado actual no coincide con el que dejó su último log de estado.
 *
 * SQL equivalente (simplificado):
 *
 *     SELECT i.*, (SELECT l.new_value FROM incident_logs l
 *                  WHERE l.incident_id = i.id
 *                    AND l.action IN ('created', 'status_changed', 'reopened')
 *                  ORDER BY l.created_at DESC, l.id DESC LIMIT 1) AS last_logged_status
 *     FROM incidents i
 *     WHERE i.status <> last_logged_status
 *       AND NOT (i.status = 'resolved'
 *                AND NOT EXISTS (SELECT 1 FROM incident_logs WHERE incident_id = i.id AND new_value = 'resolved'))
 *
 * Nunca se corrige sola: no se puede saber qué lado es el correcto.
 *
 * @implements InconsistencyCheck<Incident>
 */
class StatusMismatchesLastLog implements InconsistencyCheck
{
    public function title(): string
    {
        return 'Incidencias cuyo estado no coincide con su último log';
    }

    public function explanation(): string
    {
        return 'El estado de una incidencia debe ser el nuevo valor de su último log de estado '
            .'(creación, cambio de estado o reapertura; los comentarios no cuentan). La consulta '
            .'compara incidents.status con el new_value del log más reciente de cada incidencia '
            .'(por fecha y, si empatan, por id). Las incidencias sin ningún log de estado no se '
            .'comparan porque no hay con qué, y las resueltas sin log de resolución se dejan a la '
            .'comprobación 1 para no reportar dos veces lo mismo.';
    }

    public function query(): Builder
    {
        $lastLoggedStatus = $this->lastStatusLog('new_value');

        return Incident::query()
            ->select('incidents.*')
            ->selectSub($lastLoggedStatus, 'last_logged_status')
            ->selectSub($this->lastStatusLog('id'), 'last_log_id')
            ->selectSub($this->lastStatusLog('created_at'), 'last_log_created_at')
            ->where('incidents.status', '<>', $lastLoggedStatus)
            ->where(function (Builder $incidents) {
                // Una resuelta sin log de resolución la reporta la comprobación 1.
                $incidents->where('status', '!=', IncidentStatus::Resolved->value)
                    ->orWhereHas('logs', fn (Builder $logs) => $logs->where('new_value', IncidentStatus::Resolved->value));
            });
    }

    public function findings(): Collection
    {
        return $this->query()
            ->orderBy('incidents.id')
            ->get()
            ->map(fn (Incident $incident) => $this->findingFor($incident))
            ->values();
    }

    /**
     * Subconsulta correlacionada: una columna del log de estado más reciente de la incidencia.
     *
     * @return Builder<IncidentLog>
     */
    private function lastStatusLog(string $column): Builder
    {
        return IncidentLog::query()
            ->select($column)
            ->whereColumn('incident_logs.incident_id', 'incidents.id')
            ->affectingStatus()
            ->newestFirst()
            ->limit(1);
    }

    private function findingFor(Incident $incident): Finding
    {
        $current = $incident->status->label();
        $logged = IncidentStatus::tryFrom((string) $incident->getAttribute('last_logged_status'))?->label()
            ?? (string) $incident->getAttribute('last_logged_status');
        $logId = $incident->getAttribute('last_log_id');
        $loggedAt = Carbon::parse((string) $incident->getAttribute('last_log_created_at'))->format('d/m/Y H:i');

        return new Finding(
            $incident->id,
            "#{$incident->id} «{$incident->title}»: su estado es «{$current}», pero el último log de estado (#{$logId}, {$loggedAt}) la dejó en «{$logged}».",
            Resolution::ManualReview,
            'No se sabe qué lado es el correcto: el estado pudo cambiarse sin pasar por la aplicación (falta un log) o pudo perderse un log posterior. Revísalo a mano y, si procede, registra el cambio desde la aplicación.',
        );
    }
}
