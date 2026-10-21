<?php

namespace Tests\Feature\Diagnosis;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Services\Diagnosis\Checks\ResolvedWithoutResolutionLog;
use App\Services\Diagnosis\Resolution;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsIncidentHistory;
use Tests\TestCase;

class ResolvedWithoutResolutionLogTest extends TestCase
{
    use BuildsIncidentHistory;
    use RefreshDatabase;

    private ResolvedWithoutResolutionLog $check;

    protected function setUp(): void
    {
        parent::setUp();

        $this->check = new ResolvedWithoutResolutionLog;
    }

    public function test_it_detects_a_resolved_incident_with_no_resolution_log()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Resolved);
        $this->logCreationAndReview($incident);

        $findings = $this->check->findings();

        $this->assertCount(1, $findings);
        $this->assertSame($incident->id, $findings->first()->incidentId);
    }

    public function test_it_ignores_a_resolved_incident_whose_resolution_was_logged()
    {
        $incident = app(IncidentService::class)->create([
            'title' => fake()->sentence(),
            'priority' => 'low',
            'requester_name' => fake()->name(),
        ]);
        $service = app(IncidentService::class);
        $service->changeStatus($incident, IncidentStatus::InReview, fake()->name());
        $service->changeStatus($incident, IncidentStatus::Resolved, fake()->name());

        $this->assertCount(0, $this->check->findings());
    }

    public function test_it_ignores_incidents_that_are_not_resolved()
    {
        foreach ([IncidentStatus::Open, IncidentStatus::InReview, IncidentStatus::Blocked] as $status) {
            $this->incidentWithStatus($status);
        }

        $this->assertCount(0, $this->check->findings());
    }

    public function test_it_is_fixable_when_resolving_from_the_last_logged_status_is_a_valid_transition()
    {
        foreach ([IncidentStatus::InReview, IncidentStatus::Blocked] as $lastLogged) {
            $incident = $this->incidentWithStatus(IncidentStatus::Resolved);
            $this->logFor($incident, IncidentLogAction::Created, null, 'open', $this->at(0));
            $this->logFor($incident, IncidentLogAction::StatusChanged, 'open', $lastLogged->value, $this->at(10));
        }

        $findings = $this->check->findings();

        $this->assertCount(2, $findings);
        $this->assertTrue($findings->every(fn ($finding) => $finding->resolution === Resolution::Automatic));
    }

    public function test_it_is_fixable_when_the_incident_has_no_status_history_at_all()
    {
        $this->incidentWithStatus(IncidentStatus::Resolved);

        $this->assertSame(Resolution::Automatic, $this->check->findings()->sole()->resolution);
    }

    public function test_it_asks_for_manual_review_when_the_history_cannot_explain_the_resolution()
    {
        // El último log deja la incidencia abierta: de abierta no se puede pasar a resuelta.
        $incident = $this->incidentWithStatus(IncidentStatus::Resolved);
        $this->logFor($incident, IncidentLogAction::Created, null, 'open', $this->at(0));

        $finding = $this->check->findings()->sole();

        $this->assertSame(Resolution::ManualReview, $finding->resolution);
        $this->assertStringContainsString('Abierta', $finding->explanation);
    }

    public function test_fixing_adds_a_reconstructed_resolution_log_without_touching_the_incident()
    {
        $updatedAt = $this->at(600);
        $incident = $this->incidentWithStatus(IncidentStatus::Resolved, $updatedAt);
        $this->logCreationAndReview($incident);

        $this->check->fix($this->check->findings()->sole());

        $log = $incident->logs()->orderByDesc('id')->first();
        $this->assertSame(IncidentLogAction::StatusChanged, $log->action);
        $this->assertSame('in_review', $log->old_value);
        $this->assertSame('resolved', $log->new_value);
        $this->assertNull($log->user_name);
        $this->assertTrue($log->metadata['reconstructed']);
        $this->assertSame($updatedAt->toDateTimeString(), $log->created_at->toDateTimeString());

        $fresh = $incident->fresh();
        $this->assertSame(IncidentStatus::Resolved, $fresh->status);
        $this->assertSame($updatedAt->toDateTimeString(), $fresh->updated_at->toDateTimeString());
        $this->assertCount(0, $this->check->findings());
    }

    public function test_fixing_twice_does_not_create_a_second_log()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Resolved);
        $this->logCreationAndReview($incident);
        $finding = $this->check->findings()->sole();

        $this->check->fix($finding);
        $this->check->fix($finding);

        $this->assertSame(1, $incident->logs()->where('new_value', 'resolved')->count());
    }

    public function test_fixing_does_nothing_if_the_resolution_was_logged_after_the_diagnosis()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Resolved);
        $this->logCreationAndReview($incident);
        $finding = $this->check->findings()->sole();

        // Entre el diagnóstico y la reparación alguien registra la resolución, con una fecha
        // anterior al último log: el historial sigue "terminando" en revisión.
        $this->logFor($incident, IncidentLogAction::StatusChanged, 'in_review', 'resolved', $this->at(5));
        $logsBefore = $incident->logs()->count();

        $this->assertNull($this->check->fix($finding));

        $this->assertSame($logsBefore, $incident->logs()->count());
    }

    public function test_fixing_never_touches_an_incident_that_needs_manual_review()
    {
        $incident = $this->incidentWithStatus(IncidentStatus::Resolved);
        $this->logFor($incident, IncidentLogAction::Created, null, 'open', $this->at(0));
        $finding = $this->check->findings()->sole();

        $this->check->fix($finding);

        $this->assertSame(1, $incident->logs()->count());
        $this->assertSame(Incident::count(), 1);
    }
}
