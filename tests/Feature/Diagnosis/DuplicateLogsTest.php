<?php

namespace Tests\Feature\Diagnosis;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\IncidentLog;
use App\Services\Diagnosis\Checks\DuplicateLogs;
use App\Services\Diagnosis\Checks\ResolvedWithoutResolutionLog;
use App\Services\Diagnosis\Checks\StatusMismatchesLastLog;
use App\Services\Diagnosis\Resolution;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsIncidentHistory;
use Tests\TestCase;

class DuplicateLogsTest extends TestCase
{
    use BuildsIncidentHistory;
    use RefreshDatabase;

    private DuplicateLogs $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check = new DuplicateLogs;
    }

    public function test_it_detects_identical_consecutive_status_logs()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::InReview);
        $this->logFor($incident, IncidentLogAction::Created, null, 'open', $this->at(0));
        $first = $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));
        $copy = $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));

        $finding = $this->check->findings()->sole();

        $this->assertSame($incident->id, $finding->incidentId);
        $this->assertSame(Resolution::Automatic, $finding->resolution);
        $this->assertSame($first->id, $finding->context['keep_id']);
        $this->assertSame([$copy->id], $finding->context['delete_ids']);
    }

    public function test_it_groups_more_than_two_copies_into_one_finding_keeping_the_first()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::InReview);
        $first = $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));
        $second = $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));
        $third = $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));

        $finding = $this->check->findings()->sole();

        $this->assertSame($first->id, $finding->context['keep_id']);
        $this->assertSame([$second->id, $third->id], $finding->context['delete_ids']);
    }

    public function test_a_legitimately_repeated_transition_is_not_a_duplicate_even_in_the_same_second()
    {
        // en revisión → bloqueada → en revisión → bloqueada: el primer y el último log son
        // idénticos, pero hay otra transición entre medias.
        $service = app(IncidentService::class);
        $incident = $service->create([
            'title' => fake()->sentence(),
            'priority' => 'medium',
            'requester_name' => fake()->name(),
        ]);

        foreach ([IncidentStatus::InReview, IncidentStatus::Blocked, IncidentStatus::InReview, IncidentStatus::Blocked] as $status) {
            $service->changeStatus($incident, $status, 'Ana Torres');
        }

        $this->assertCount(0, $this->check->findings());
    }

    public function test_logs_that_differ_in_any_field_are_not_duplicates()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::InReview);
        $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10), 'Ana Torres');
        $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10), 'Luis Vega');
        $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(11), 'Ana Torres');

        $this->assertCount(0, $this->check->findings());
    }

    public function test_two_comments_logged_in_the_same_second_are_not_duplicates()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Open);
        $this->logFor($incident, IncidentLogAction::Commented, null, null, $this->at(5), 'Ana Torres', ['comment_id' => 1]);
        $this->logFor($incident, IncidentLogAction::Commented, null, null, $this->at(5), 'Ana Torres', ['comment_id' => 2]);

        $this->assertCount(0, $this->check->findings());
    }

    public function test_the_same_comment_logged_twice_is_a_duplicate()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Open);
        $first = $this->logFor($incident, IncidentLogAction::Commented, null, null, $this->at(5), 'Ana Torres', ['comment_id' => 7]);
        $copy = $this->logFor($incident, IncidentLogAction::Commented, null, null, $this->at(5), 'Ana Torres', ['comment_id' => 7]);

        $finding = $this->check->findings()->sole();

        $this->assertSame($first->id, $finding->context['keep_id']);
        $this->assertSame([$copy->id], $finding->context['delete_ids']);
    }

    public function test_identical_logs_on_different_incidents_are_not_duplicates()
    {
        foreach (range(1, 2) as $ignored) {
            $incident = $this->incidentWithStatus(IncidentStatus::InReview);
            $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));
        }

        $this->assertCount(0, $this->check->findings());
    }

    public function test_fixing_deletes_only_the_extra_copies_and_keeps_the_first()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::InReview);
        $this->logFor($incident, IncidentLogAction::Created, null, 'open', $this->at(0));
        $first = $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));
        $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));

        $this->check->fix($this->check->findings()->sole());

        $this->assertSame(2, $incident->logs()->count());
        $this->assertTrue(IncidentLog::query()->whereKey($first->id)->exists());
        $this->assertCount(0, $this->check->findings());
    }

    public function test_fixing_does_not_delete_a_log_that_is_no_longer_identical_to_the_one_kept()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::InReview);
        $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));
        $copy = $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));
        $finding = $this->check->findings()->sole();

        // Alguien corrige la copia entre el diagnóstico y la reparación.
        $copy->forceFill(['user_name' => 'Luis Vega'])->save();
        $this->check->fix($finding);

        $this->assertTrue(IncidentLog::query()->whereKey($copy->id)->exists());
    }

    public function test_fixing_leaves_the_other_checks_clean()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::InReview);
        $this->logFor($incident, IncidentLogAction::Created, null, 'open', $this->at(0));
        $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));
        $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', 'in_review', $this->at(10));

        $this->check->fix($this->check->findings()->sole());

        $this->assertCount(0, (new StatusMismatchesLastLog)->findings());
        $this->assertCount(0, (new ResolvedWithoutResolutionLog)->findings());
    }
}
