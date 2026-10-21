<?php

namespace Tests\Feature\Diagnosis;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\InconsistentIncidentsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnoseCommandTest extends TestCase
{
    use RefreshDatabase;

    private function incidentTitled(string $title): Incident
    {
        return Incident::query()->where('title', $title)->sole();
    }

    public function test_a_database_without_problems_reports_nothing_and_succeeds()
    {
        Incident::factory()->count(2)->create();

        $this->artisan('incidents:diagnose')
            ->expectsOutputToContain('No se encontraron inconsistencias.')
            ->assertExitCode(0);
    }

    public function test_without_fix_it_only_reports_and_writes_nothing()
    {
        $this->seed(DatabaseSeeder::class);
        $logsBefore = IncidentLog::query()->pluck('id')->all();

        $this->artisan('incidents:diagnose')
            ->expectsOutputToContain('Modo simulación (dry-run)')
            ->expectsOutputToContain(InconsistentIncidentsSeeder::RESOLVED_WITHOUT_LOG_TITLE)
            ->expectsOutputToContain(InconsistentIncidentsSeeder::STATUS_MISMATCH_TITLE)
            ->expectsOutputToContain(InconsistentIncidentsSeeder::DUPLICATE_LOGS_TITLE)
            ->expectsOutputToContain('Se podrían corregir automáticamente: 2')
            ->expectsOutputToContain('Requieren revisión manual: 1')
            ->assertExitCode(1);

        $this->assertSame($logsBefore, IncidentLog::query()->pluck('id')->all());
    }

    public function test_fix_applies_only_the_safe_corrections_and_leaves_the_ambiguous_one()
    {
        $this->seed(DatabaseSeeder::class);
        $resolved = $this->incidentTitled(InconsistentIncidentsSeeder::RESOLVED_WITHOUT_LOG_TITLE);
        $mismatch = $this->incidentTitled(InconsistentIncidentsSeeder::STATUS_MISMATCH_TITLE);
        $duplicated = $this->incidentTitled(InconsistentIncidentsSeeder::DUPLICATE_LOGS_TITLE);
        $mismatchLogs = $mismatch->logs()->count();
        $mismatchStatus = $mismatch->status;
        $duplicatedLogs = $duplicated->logs()->count();

        $this->artisan('incidents:diagnose --fix')
            ->expectsOutputToContain('Modo corrección (--fix)')
            ->expectsOutputToContain('Requieren revisión manual: 1')
            ->assertExitCode(1);

        // Resuelta sin log: ahora tiene su log de resolución reconstruido.
        $this->assertSame(1, $resolved->logs()->where('new_value', 'resolved')->count());
        $this->assertTrue($resolved->logs()->where('new_value', 'resolved')->sole()->metadata['reconstructed']);
        $this->assertSame(IncidentStatus::Resolved, $resolved->fresh()->status);

        // Duplicados: queda una sola copia.
        $this->assertSame($duplicatedLogs - 1, $duplicated->logs()->count());

        // Estado distinto del último log: no se toca nada.
        $this->assertSame($mismatchLogs, $mismatch->logs()->count());
        $this->assertSame($mismatchStatus, $mismatch->fresh()->status);
    }

    public function test_after_fixing_only_the_manual_review_case_remains()
    {
        $this->seed(DatabaseSeeder::class);
        $this->artisan('incidents:diagnose --fix');
        $logsAfterFix = IncidentLog::count();

        $this->artisan('incidents:diagnose')
            ->expectsOutputToContain(InconsistentIncidentsSeeder::STATUS_MISMATCH_TITLE)
            ->doesntExpectOutputToContain(InconsistentIncidentsSeeder::RESOLVED_WITHOUT_LOG_TITLE)
            ->doesntExpectOutputToContain(InconsistentIncidentsSeeder::DUPLICATE_LOGS_TITLE)
            ->expectsOutputToContain('Requieren revisión manual: 1')
            ->assertExitCode(1);

        $this->assertSame($logsAfterFix, IncidentLog::count());
    }

    public function test_the_sql_option_prints_the_detection_queries()
    {
        // Cada texto aparece solo en la consulta de una comprobación (Mockery asigna cada
        // línea a la primera expectativa que coincide, así que no pueden solaparse).
        $this->artisan('incidents:diagnose --sql')
            ->expectsOutputToContain('not exists (select')
            ->expectsOutputToContain('as "last_logged_status"')
            ->expectsOutputToContain('group by "incident_id"')
            ->assertExitCode(0);
    }
}
