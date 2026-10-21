<?php

namespace App\Services\Diagnosis\Checks;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use App\Services\Diagnosis\Finding;
use App\Services\Diagnosis\FixesInconsistencies;
use App\Services\Diagnosis\InconsistencyCheck;
use App\Services\Diagnosis\Resolution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Incidencias que están resueltas pero cuya resolución no quedó registrada.
 *
 * SQL equivalente:
 *
 *     SELECT * FROM incidents i
 *     WHERE i.status = 'resolved'
 *       AND NOT EXISTS (SELECT 1 FROM incident_logs l
 *                       WHERE l.incident_id = i.id AND l.new_value = 'resolved')
 *
 * @implements InconsistencyCheck<Incident>
 */
class ResolvedWithoutResolutionLog implements FixesInconsistencies, InconsistencyCheck
{
    public function title(): string
    {
        return 'Incidencias resueltas sin log de resolución';
    }

    public function explanation(): string
    {
        return 'Toda incidencia «Resuelta» debería tener un log cuyo nuevo valor sea «resolved». '
            .'La consulta busca incidencias con status = resolved para las que no existe ningún '
            .'log con new_value = resolved: el estado cambió sin dejar rastro.';
    }

    public function query(): Builder
    {
        return Incident::query()
            ->where('status', IncidentStatus::Resolved->value)
            ->whereDoesntHave('logs', fn (Builder $logs) => $logs->where('new_value', IncidentStatus::Resolved->value));
    }

    public function findings(): Collection
    {
        return $this->query()
            ->orderBy('id')
            ->get()
            ->map(fn (Incident $incident) => $this->findingFor($incident))
            ->values();
    }

    public function fix(Finding $finding): ?string
    {
        return DB::transaction(function () use ($finding) {
            $incident = Incident::query()->lockForUpdate()->find($finding->incidentId);

            if ($incident === null || ! $this->query()->whereKey($incident->id)->exists()) {
                return null;
            }

            $current = $this->findingFor($incident);

            if (! $current->isAutomatic()) {
                return null;
            }

            $log = new IncidentLog([
                'action' => IncidentLogAction::StatusChanged,
                'old_value' => $current->context['last_logged_status'],
                'new_value' => IncidentStatus::Resolved->value,
                'user_name' => null,
                'metadata' => [
                    'reconstructed' => true,
                    'reason' => 'Log de resolución ausente; reconstruido por incidents:diagnose.',
                ],
            ]);
            $log->incident_id = $incident->id;
            // La mejor estimación de cuándo se resolvió es la última modificación de la fila.
            $log->created_at = $incident->updated_at;
            $log->save();

            return "Creado el log de resolución reconstruido de «{$incident->title}» (#{$incident->id}).";
        });
    }

    private function findingFor(Incident $incident): Finding
    {
        $last = $incident->logs()->affectingStatus()->newestFirst()->first();
        $lastStatus = $last === null ? null : IncidentStatus::tryFrom((string) $last->new_value);
        $subject = "#{$incident->id} «{$incident->title}»";
        $summary = "{$subject}: está «Resuelta», pero ningún log registra la resolución.";

        if ($last === null) {
            return new Finding(
                $incident->id,
                $summary,
                Resolution::Automatic,
                'No tiene historial de estados. Se crearía un log de resolución reconstruido, marcado como tal en metadata.',
                ['last_logged_status' => null],
            );
        }

        if ($lastStatus === null) {
            return new Finding(
                $incident->id,
                $summary,
                Resolution::ManualReview,
                "El último log de estado tiene un valor desconocido («{$last->new_value}»); no se puede deducir cómo se llegó a «Resuelta».",
            );
        }

        if (! $lastStatus->canTransitionTo(IncidentStatus::Resolved)) {
            return new Finding(
                $incident->id,
                $summary,
                Resolution::ManualReview,
                "El último log de estado la deja en «{$lastStatus->label()}» y de ahí no se puede pasar a «Resuelta»: el historial no explica la resolución.",
            );
        }

        return new Finding(
            $incident->id,
            $summary,
            Resolution::Automatic,
            "Pasar de «{$lastStatus->label()}» a «Resuelta» es una transición válida. Se crearía un log de resolución reconstruido, marcado como tal en metadata y fechado en la última modificación de la incidencia.",
            ['last_logged_status' => $lastStatus->value],
        );
    }
}
