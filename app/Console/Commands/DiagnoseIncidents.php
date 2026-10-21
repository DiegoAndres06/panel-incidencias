<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Models\IncidentLog;
use App\Services\Diagnosis\Checks\DuplicateLogs;
use App\Services\Diagnosis\Checks\ResolvedWithoutResolutionLog;
use App\Services\Diagnosis\Checks\StatusMismatchesLastLog;
use App\Services\Diagnosis\FixesInconsistencies;
use App\Services\Diagnosis\InconsistencyCheck;
use Illuminate\Console\Command;

class DiagnoseIncidents extends Command
{
    /**
     * @var string
     */
    protected $signature = 'incidents:diagnose
        {--fix : Aplica las correcciones seguras. Sin esta opción solo informa (dry-run)}
        {--sql : Muestra la consulta de detección de cada comprobación}';

    /**
     * @var string
     */
    protected $description = 'Detecta inconsistencias entre las incidencias y su historial y, con --fix, corrige las seguras';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');

        $this->components->info('Diagnóstico de incidencias');
        $this->line($fix
            ? '<fg=yellow>Modo corrección (--fix): se aplican solo las correcciones seguras.</>'
            : 'Modo simulación (dry-run): no se modifica nada. Usa --fix para aplicar las correcciones seguras.');

        $automatic = 0;
        $manual = 0;
        $fixed = 0;
        $skipped = 0;

        foreach ($this->checks() as $number => $check) {
            $findings = $check->findings();

            $this->newLine();
            $this->line(sprintf('<options=bold>%d. %s</> (%d)', $number + 1, $check->title(), $findings->count()));
            $this->line('   '.wordwrap($check->explanation(), 100, "\n   "));

            if ($this->option('sql')) {
                $this->line('   <fg=gray>'.$check->query()->toRawSql().'</>');
            }

            if ($findings->isEmpty()) {
                $this->line('   Sin hallazgos.');

                continue;
            }

            foreach ($findings as $finding) {
                $this->line("   • {$finding->summary}");

                if (! $finding->isAutomatic()) {
                    $manual++;
                    $this->line("     <fg=red>Requiere revisión manual:</> {$finding->explanation}");

                    continue;
                }

                if (! $fix || ! $check instanceof FixesInconsistencies) {
                    $automatic++;
                    $this->line("     <fg=green>Corrección segura:</> {$finding->explanation}");

                    continue;
                }

                $result = $check->fix($finding);

                if ($result === null) {
                    $skipped++;
                    $this->line('     <fg=yellow>Omitido:</> ya no aplicaba o cambió desde el diagnóstico.');

                    continue;
                }

                $fixed++;
                $this->line("     <fg=green>Corregido:</> {$result}");
            }
        }

        $this->newLine();

        if ($automatic + $manual + $fixed + $skipped === 0) {
            $this->components->info('No se encontraron inconsistencias.');

            return self::SUCCESS;
        }

        $this->summary($fix, $automatic, $manual, $fixed, $skipped);

        // Falla si queda algo por resolver: sirve para usarlo en scripts o en CI.
        return $manual > 0 || $automatic > 0 || $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function summary(bool $fix, int $automatic, int $manual, int $fixed, int $skipped): void
    {
        $this->line('<options=bold>Resumen</>');

        if ($fix) {
            $this->line("  Corregidas automáticamente: {$fixed}");
            $this->line("  Omitidas: {$skipped}");
        } else {
            $this->line("  Se podrían corregir automáticamente: {$automatic}");
        }

        $this->line("  Requieren revisión manual: {$manual}");

        if (! $fix && $automatic > 0) {
            $this->newLine();
            $this->line('Vuelve a ejecutar con --fix para aplicar las correcciones seguras.');
        }
    }

    /**
     * @return array<int, InconsistencyCheck<Incident>|InconsistencyCheck<IncidentLog>>
     */
    private function checks(): array
    {
        return [
            new ResolvedWithoutResolutionLog,
            new StatusMismatchesLastLog,
            new DuplicateLogs,
        ];
    }
}
