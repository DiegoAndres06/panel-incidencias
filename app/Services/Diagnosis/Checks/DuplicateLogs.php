<?php

namespace App\Services\Diagnosis\Checks;

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
 * Registros de trazabilidad repetidos.
 *
 * Paso 1, en SQL: localizar los grupos de registros iguales.
 *
 *     SELECT incident_id, action, old_value, new_value, user_name, created_at, COUNT(*) AS copies
 *     FROM incident_logs
 *     GROUP BY incident_id, action, old_value, new_value, user_name, created_at
 *     HAVING COUNT(*) > 1
 *
 * Paso 2, en PHP, solo sobre las incidencias del paso 1: se comparan también los metadatos
 * y se descarta la repetición legítima (ver `findingsFor`).
 *
 * @implements InconsistencyCheck<IncidentLog>
 */
class DuplicateLogs implements FixesInconsistencies, InconsistencyCheck
{
    public function title(): string
    {
        return 'Logs duplicados';
    }

    public function explanation(): string
    {
        return 'Dos logs idénticos (misma incidencia, acción, valores, autor, instante y metadatos) '
            .'seguidos, sin otra transición entre medias, sobran: la aplicación no puede generarlos, '
            .'porque cada cambio de estado se valida contra el estado actual. La consulta agrupa por '
            .'todos esos campos y se queda con los grupos de más de un registro. Después se comparan '
            .'los metadatos y se descarta la repetición legítima (por ejemplo, «en revisión → '
            .'bloqueada» dos veces con un desbloqueo en medio).';
    }

    public function query(): Builder
    {
        return IncidentLog::query()
            ->select('incident_id', 'action', 'old_value', 'new_value', 'user_name', 'created_at')
            ->selectRaw('count(*) as copies')
            ->groupBy('incident_id', 'action', 'old_value', 'new_value', 'user_name', 'created_at')
            ->havingRaw('count(*) > 1');
    }

    public function findings(): Collection
    {
        $incidentIds = $this->query()->pluck('incident_id')->unique();

        return Incident::query()
            ->whereIn('id', $incidentIds)
            ->orderBy('id')
            ->get()
            ->flatMap(fn (Incident $incident) => $this->findingsFor($incident))
            ->values();
    }

    public function fix(Finding $finding): ?string
    {
        return DB::transaction(function () use ($finding) {
            /** @var int $keepId */
            $keepId = $finding->context['keep_id'];
            /** @var array<int, int> $deleteIds */
            $deleteIds = $finding->context['delete_ids'];

            $keep = IncidentLog::query()->lockForUpdate()->find($keepId);

            if ($keep === null) {
                return null;
            }

            // Solo se borra lo que sigue siendo idéntico al registro que se conserva.
            $extraIds = IncidentLog::query()
                ->whereIn('id', $deleteIds)
                ->where('incident_id', $keep->incident_id)
                ->get()
                ->filter(fn (IncidentLog $log) => $this->fingerprint($log) === $this->fingerprint($keep))
                ->pluck('id');

            if ($extraIds->isEmpty()) {
                return null;
            }

            IncidentLog::query()->whereIn('id', $extraIds)->delete();

            return "Eliminadas {$extraIds->count()} copia(s) del log #{$keep->id} de la incidencia #{$keep->incident_id}.";
        });
    }

    /**
     * Hallazgos de una incidencia, uno por cada registro que se conserva con copias.
     *
     * Los registros de estado (creación, cambio, reapertura) solo cuentan como copia si son
     * idénticos al estado anterior inmediato: así la misma transición repetida de forma
     * legítima —con otra transición en medio— no se confunde con un duplicado. El resto de
     * registros (comentarios) cuentan como copia si ya hubo uno idéntico antes.
     *
     * @return Collection<int, Finding>
     */
    private function findingsFor(Incident $incident): Collection
    {
        /** @var array<int, array<int, int>> $extrasByKeptLog */
        $extrasByKeptLog = [];
        $previousStatusLog = null;
        $seen = [];

        $logs = $incident->logs()->orderBy('created_at')->orderBy('id')->get();

        foreach ($logs as $log) {
            $fingerprint = $this->fingerprint($log);

            if ($log->action->affectsStatus()) {
                if ($previousStatusLog !== null && $previousStatusLog['fingerprint'] === $fingerprint) {
                    $extrasByKeptLog[$previousStatusLog['id']][] = $log->id;

                    continue;
                }

                $previousStatusLog = ['id' => $log->id, 'fingerprint' => $fingerprint];

                continue;
            }

            if (isset($seen[$fingerprint])) {
                $extrasByKeptLog[$seen[$fingerprint]][] = $log->id;

                continue;
            }

            $seen[$fingerprint] = $log->id;
        }

        return collect($extrasByKeptLog)->map(function (array $extraIds, int $keepId) use ($incident, $logs) {
            $kept = $logs->firstWhere('id', $keepId);
            $copies = count($extraIds) + 1;
            $what = $kept->action->label();
            $values = $kept->old_value === null && $kept->new_value === null
                ? ''
                : " ({$kept->old_value} → {$kept->new_value})";
            $ids = '#'.implode(', #', $extraIds);

            return new Finding(
                $incident->id,
                "#{$incident->id} «{$incident->title}»: {$copies} logs idénticos de {$what}{$values} de {$kept->user_name} el {$kept->created_at?->format('d/m/Y H:i:s')}.",
                Resolution::Automatic,
                "Se conservaría el log #{$keepId} y se eliminarían las copias {$ids}. Son idénticas en todos sus campos, así que no se pierde información.",
                ['keep_id' => $keepId, 'delete_ids' => $extraIds],
            );
        })->values();
    }

    /**
     * Huella de todos los campos que hacen que dos registros sean "el mismo".
     */
    private function fingerprint(IncidentLog $log): string
    {
        return (string) json_encode([
            $log->incident_id,
            $log->action->value,
            $log->old_value,
            $log->new_value,
            $log->user_name,
            $log->created_at?->toDateTimeString(),
            $this->normalized($log->metadata),
        ]);
    }

    /**
     * Ordena las claves de los metadatos: MySQL no conserva el orden en que se escribieron.
     */
    private function normalized(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        ksort($value);

        return array_map(fn ($item) => $this->normalized($item), $value);
    }
}
