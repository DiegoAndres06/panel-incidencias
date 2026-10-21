<?php

namespace Tests\Feature;

use App\Enums\IncidentStatus;
use App\Models\Assignee;
use App\Models\Incident;
use App\Services\Diagnosis\Checks\DuplicateLogs;
use App\Services\Diagnosis\Checks\ResolvedWithoutResolutionLog;
use App\Services\Diagnosis\Checks\StatusMismatchesLastLog;
use App\Services\Diagnosis\Resolution;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\InconsistentIncidentsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeders_create_sample_incidents_in_every_status()
    {
        $this->seed(DatabaseSeeder::class);

        foreach (IncidentStatus::cases() as $status) {
            $this->assertTrue(Incident::query()->where('status', $status->value)->exists(), "No hay incidencias en estado {$status->value}");
        }
    }

    public function test_the_seeded_data_contains_exactly_the_three_documented_inconsistencies()
    {
        $this->seed(DatabaseSeeder::class);

        $resolved = (new ResolvedWithoutResolutionLog)->findings()->sole();
        $mismatch = (new StatusMismatchesLastLog)->findings()->sole();
        $duplicates = (new DuplicateLogs)->findings()->sole();

        $this->assertSame(InconsistentIncidentsSeeder::RESOLVED_WITHOUT_LOG_TITLE, Incident::findOrFail($resolved->incidentId)->title);
        $this->assertSame(Resolution::Automatic, $resolved->resolution);

        $this->assertSame(InconsistentIncidentsSeeder::STATUS_MISMATCH_TITLE, Incident::findOrFail($mismatch->incidentId)->title);
        $this->assertSame(Resolution::ManualReview, $mismatch->resolution);

        $this->assertSame(InconsistentIncidentsSeeder::DUPLICATE_LOGS_TITLE, Incident::findOrFail($duplicates->incidentId)->title);
        $this->assertSame(Resolution::Automatic, $duplicates->resolution);
    }

    public function test_the_sample_incidents_have_a_coherent_history()
    {
        $this->seed(DatabaseSeeder::class);

        $inconsistentTitles = [
            InconsistentIncidentsSeeder::RESOLVED_WITHOUT_LOG_TITLE,
            InconsistentIncidentsSeeder::STATUS_MISMATCH_TITLE,
            InconsistentIncidentsSeeder::DUPLICATE_LOGS_TITLE,
        ];

        $flagged = collect([new ResolvedWithoutResolutionLog, new StatusMismatchesLastLog, new DuplicateLogs])
            ->flatMap(fn ($check) => $check->findings())
            ->map(fn ($finding) => Incident::findOrFail($finding->incidentId)->title)
            ->unique()
            ->sort()
            ->values()
            ->all();

        sort($inconsistentTitles);

        $this->assertSame($inconsistentTitles, $flagged);
    }

    public function test_the_seeders_create_the_assignee_catalog()
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Assignee::count());
    }

    public function test_every_assignee_used_by_the_seeded_incidents_exists_in_the_catalog()
    {
        $this->seed(DatabaseSeeder::class);

        $used = Incident::query()->whereNotNull('assigned_to')->distinct()->pluck('assigned_to')->sort()->values()->all();
        $missing = array_diff($used, Assignee::query()->pluck('name')->all());

        $this->assertNotEmpty($used);
        $this->assertSame([], array_values($missing), 'Hay responsables sembrados que no están en el catálogo.');
    }

    public function test_seeding_on_a_database_that_already_has_incidents_only_completes_the_catalog()
    {
        $existing = Incident::factory()->create();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Incident::count());
        $this->assertTrue(Incident::query()->whereKey($existing->id)->exists());
        $this->assertGreaterThan(0, Assignee::count());
    }

    public function test_seeding_the_catalog_twice_does_not_duplicate_the_assignees()
    {
        $this->seed(DatabaseSeeder::class);
        $count = Assignee::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($count, Assignee::count());
    }

    public function test_seeding_twice_does_not_duplicate_the_data()
    {
        $this->seed(DatabaseSeeder::class);
        $count = Incident::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($count, Incident::count());
    }
}
