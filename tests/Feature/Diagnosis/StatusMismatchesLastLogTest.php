<?php

namespace Tests\Feature\Diagnosis;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Services\Diagnosis\Checks\ResolvedWithoutResolutionLog;
use App\Services\Diagnosis\Checks\StatusMismatchesLastLog;
use App\Services\Diagnosis\Resolution;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsIncidentHistory;
use Tests\TestCase;

class StatusMismatchesLastLogTest extends TestCase
{
    use BuildsIncidentHistory;
    use RefreshDatabase;

    private StatusMismatchesLastLog $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check = new StatusMismatchesLastLog;
    }

    public function test_it_detects_a_status_that_differs_from_the_last_status_log()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Blocked);
        $this->logCreationAndReview($incident);

        $finding = $this->check->findings()->sole();

        $this->assertSame($incident->id, $finding->incidentId);
        $this->assertStringContainsString('Bloqueada', $finding->summary);
        $this->assertStringContainsString('En revisión', $finding->summary);
    }

    public function test_it_never_fixes_automatically_because_it_cannot_know_which_side_is_right()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Blocked);
        $this->logCreationAndReview($incident);

        $this->assertSame(Resolution::ManualReview, $this->check->findings()->sole()->resolution);
    }

    public function test_it_ignores_an_incident_whose_status_matches_its_last_status_log()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::InReview);
        $this->logCreationAndReview($incident);

        $this->assertCount(0, $this->check->findings());
    }

    public function test_comments_logged_after_the_last_status_change_do_not_count()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::InReview);
        $this->logCreationAndReview($incident);
        $this->logFor($incident, IncidentLogAction::Commented, null, null, $this->at(500), 'Ana Torres', ['comment_id' => 1]);

        $this->assertCount(0, $this->check->findings());
    }

    public function test_a_comment_logged_after_the_last_status_change_does_not_hide_a_mismatch()
    {
        // Un comentario no tiene new_value: si contara como "último log", compararía el
        // estado con NULL y el desajuste pasaría desapercibido.
        $incident = $this->incidentWithStatus(IncidentStatus::Blocked);
        $this->logCreationAndReview($incident);
        $this->logFor($incident, IncidentLogAction::Commented, null, null, $this->at(500), 'Ana Torres', ['comment_id' => 1]);

        $this->assertSame($incident->id, $this->check->findings()->sole()->incidentId);
    }

    public function test_it_uses_the_most_recent_status_log_not_the_first_one()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Blocked);
        $this->logCreationAndReview($incident);
        $this->logFor($incident, IncidentLogAction::StatusChanged, 'in_review', 'blocked', $this->at(20));

        $this->assertCount(0, $this->check->findings());
    }

    public function test_it_ignores_incidents_without_any_status_log_because_there_is_nothing_to_compare()
    {
        $this->incidentWithStatus(IncidentStatus::Blocked);

        $this->assertCount(0, $this->check->findings());
    }

    public function test_a_resolved_incident_without_resolution_log_is_left_to_the_other_check()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Resolved);
        $this->logCreationAndReview($incident);

        $this->assertCount(0, $this->check->findings());
        $this->assertCount(1, (new ResolvedWithoutResolutionLog)->findings());
    }

    public function test_a_properly_logged_flow_through_the_service_is_never_flagged()
    {
        $service = app(IncidentService::class);
        $incident = $service->create([
            'title' => fake()->sentence(),
            'priority' => 'high',
            'requester_name' => fake()->name(),
        ]);

        foreach ([IncidentStatus::InReview, IncidentStatus::Resolved] as $status) {
            $service->changeStatus($incident, $status, fake()->name());
        }
        $service->changeStatus($incident, IncidentStatus::InReview, fake()->name(), fake()->sentence());

        $this->assertCount(0, $this->check->findings());
    }
}
