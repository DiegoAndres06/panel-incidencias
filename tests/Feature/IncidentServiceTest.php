<?php

namespace Tests\Feature;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentLog;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class IncidentServiceTest extends TestCase
{
    use RefreshDatabase;

    private IncidentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IncidentService::class);
    }

    public function test_creating_an_incident_starts_it_open_and_logs_its_creation()
    {
        $requester = fake()->name();

        $incident = $this->service->create([
            'title' => fake()->sentence(),
            'description' => null,
            'priority' => IncidentPriority::High->value,
            'requester_name' => $requester,
            'assigned_to' => null,
        ]);

        $this->assertSame(IncidentStatus::Open, $incident->fresh()->status);

        $log = $incident->logs()->sole();
        $this->assertSame(IncidentLogAction::Created, $log->action);
        $this->assertNull($log->old_value);
        $this->assertSame(IncidentStatus::Open->value, $log->new_value);
        $this->assertSame($requester, $log->user_name);
    }

    public function test_changing_the_status_updates_the_incident_and_logs_old_new_actor_and_reason()
    {
        $incident = Incident::factory()->withStatus(IncidentStatus::InReview)->create();
        $actor = fake()->name();
        $reason = fake()->sentence();

        $this->service->changeStatus($incident, IncidentStatus::Blocked, $actor, $reason);

        $this->assertSame(IncidentStatus::Blocked, $incident->fresh()->status);

        $log = $incident->logs()->sole();
        $this->assertSame(IncidentLogAction::StatusChanged, $log->action);
        $this->assertSame(IncidentStatus::InReview->value, $log->old_value);
        $this->assertSame(IncidentStatus::Blocked->value, $log->new_value);
        $this->assertSame($actor, $log->user_name);
        $this->assertSame(['reason' => $reason], $log->metadata);
        $this->assertNotNull($log->created_at);
    }

    public function test_a_status_change_without_a_reason_stores_no_metadata()
    {
        $incident = Incident::factory()->create();

        $this->service->changeStatus($incident, IncidentStatus::InReview, fake()->name(), null);

        $this->assertNull($incident->logs()->sole()->metadata);
    }

    public function test_an_invalid_transition_is_rejected_and_writes_nothing()
    {
        $incident = Incident::factory()->create();

        try {
            $this->service->changeStatus($incident, IncidentStatus::Resolved, fake()->name());
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame(
                'No se puede pasar de «Abierta» a «Resuelta».',
                $e->errors()['status'][0],
            );
        }

        $this->assertSame(IncidentStatus::Open, $incident->fresh()->status);
        $this->assertSame(0, IncidentLog::count());
    }

    public function test_moving_to_the_same_status_is_rejected()
    {
        $incident = Incident::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->changeStatus($incident, IncidentStatus::Open, fake()->name());
    }

    public function test_the_transition_is_checked_against_the_current_database_status_not_a_stale_model()
    {
        $incident = Incident::factory()->create();
        $staleCopy = Incident::findOrFail($incident->id);

        // Otra persona resuelve la incidencia mientras esta copia sigue creyendo que está abierta.
        $incident->update(['status' => IncidentStatus::Resolved]);

        $this->expectException(ValidationException::class);

        $this->service->changeStatus($staleCopy, IncidentStatus::InReview, fake()->name());
    }

    public function test_reopening_a_resolved_incident_requires_a_reason_and_writes_nothing_without_it()
    {
        $incident = Incident::factory()->withStatus(IncidentStatus::Resolved)->create();

        try {
            $this->service->changeStatus($incident, IncidentStatus::InReview, fake()->name(), '   ');
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame('Indica el motivo de la reapertura.', $e->errors()['note'][0]);
        }

        $this->assertSame(IncidentStatus::Resolved, $incident->fresh()->status);
        $this->assertSame(0, IncidentLog::count());
    }

    public function test_reopening_logs_an_explicit_reopened_action_with_the_reason()
    {
        $incident = Incident::factory()->withStatus(IncidentStatus::Resolved)->create();
        $reason = fake()->sentence();

        $this->service->changeStatus($incident, IncidentStatus::InReview, fake()->name(), $reason);

        $log = $incident->logs()->sole();
        $this->assertSame(IncidentLogAction::Reopened, $log->action);
        $this->assertSame(IncidentStatus::Resolved->value, $log->old_value);
        $this->assertSame(IncidentStatus::InReview->value, $log->new_value);
        $this->assertSame(['reason' => $reason], $log->metadata);
        $this->assertSame(IncidentStatus::InReview, $incident->fresh()->status);
    }

    public function test_a_resolved_incident_cannot_go_straight_back_to_open()
    {
        $incident = Incident::factory()->withStatus(IncidentStatus::Resolved)->create();

        try {
            $this->service->changeStatus($incident, IncidentStatus::Open, fake()->name(), fake()->sentence());
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException) {
            // esperado
        }

        $this->assertSame(IncidentStatus::Resolved, $incident->fresh()->status);
        $this->assertSame(0, IncidentLog::count());
    }

    public function test_if_writing_the_log_fails_the_status_change_is_rolled_back()
    {
        $incident = Incident::factory()->create();

        $this->app['events']->listen(
            'eloquent.creating: '.IncidentLog::class,
            fn () => throw new RuntimeException('fallo simulado al escribir el log'),
        );

        try {
            $this->service->changeStatus($incident, IncidentStatus::InReview, fake()->name());
            $this->fail('Se esperaba una RuntimeException.');
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertSame(IncidentStatus::Open, $incident->fresh()->status);
        $this->assertSame(0, IncidentLog::count());
    }

    public function test_if_writing_the_log_fails_the_incident_creation_is_rolled_back()
    {
        $this->app['events']->listen(
            'eloquent.creating: '.IncidentLog::class,
            fn () => throw new RuntimeException('fallo simulado al escribir el log'),
        );

        try {
            $this->service->create([
                'title' => fake()->sentence(),
                'priority' => IncidentPriority::Low->value,
                'requester_name' => fake()->name(),
            ]);
            $this->fail('Se esperaba una RuntimeException.');
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertSame(0, Incident::count());
    }

    public function test_reassigning_updates_the_assignee_and_logs_old_new_actor_and_reason()
    {
        $incident = Incident::factory()->create(['assigned_to' => 'Laura Gómez']);
        $actor = fake()->name();
        $reason = fake()->sentence();

        $this->service->reassign($incident, 'Carlos Peña', $actor, $reason);

        $this->assertSame('Carlos Peña', $incident->fresh()->assigned_to);

        $log = $incident->logs()->sole();
        $this->assertSame(IncidentLogAction::Assigned, $log->action);
        $this->assertSame('Laura Gómez', $log->old_value);
        $this->assertSame('Carlos Peña', $log->new_value);
        $this->assertSame($actor, $log->user_name);
        $this->assertSame(['reason' => $reason], $log->metadata);
    }

    public function test_assigning_an_unassigned_incident_logs_a_null_old_value()
    {
        $incident = Incident::factory()->create(['assigned_to' => null]);

        $this->service->reassign($incident, 'Carlos Peña', fake()->name());

        $log = $incident->logs()->sole();
        $this->assertNull($log->old_value);
        $this->assertSame('Carlos Peña', $log->new_value);
        $this->assertNull($log->metadata);
    }

    public function test_removing_the_assignee_logs_a_null_new_value()
    {
        $incident = Incident::factory()->create(['assigned_to' => 'Laura Gómez']);

        $this->service->reassign($incident, null, fake()->name());

        $this->assertNull($incident->fresh()->assigned_to);
        $this->assertNull($incident->logs()->sole()->new_value);
    }

    public function test_reassigning_never_changes_the_status()
    {
        $incident = Incident::factory()->withStatus(IncidentStatus::Blocked)->create();

        $this->service->reassign($incident, 'Carlos Peña', fake()->name());

        $this->assertSame(IncidentStatus::Blocked, $incident->fresh()->status);
    }

    public function test_assigning_the_same_person_is_rejected_and_writes_nothing()
    {
        $incident = Incident::factory()->create(['assigned_to' => 'Laura Gómez']);

        try {
            $this->service->reassign($incident, 'Laura Gómez', fake()->name());
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame('La incidencia ya está asignada a «Laura Gómez».', $e->errors()['assigned_to'][0]);
        }

        $this->assertSame(0, IncidentLog::count());
    }

    public function test_removing_the_assignee_of_an_unassigned_incident_is_rejected()
    {
        $incident = Incident::factory()->create(['assigned_to' => null]);

        try {
            $this->service->reassign($incident, null, fake()->name());
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame('La incidencia ya está sin asignar.', $e->errors()['assigned_to'][0]);
        }

        $this->assertSame(0, IncidentLog::count());
    }

    public function test_reassigning_is_checked_against_the_current_database_value_not_a_stale_model()
    {
        $incident = Incident::factory()->create(['assigned_to' => null]);
        $staleCopy = Incident::findOrFail($incident->id);

        // Otra persona asigna a Carlos mientras esta copia sigue creyendo que no hay responsable.
        $incident->update(['assigned_to' => 'Carlos Peña']);

        $this->expectException(ValidationException::class);

        $this->service->reassign($staleCopy, 'Carlos Peña', fake()->name());
    }

    public function test_if_writing_the_log_fails_the_reassignment_is_rolled_back()
    {
        $incident = Incident::factory()->create(['assigned_to' => 'Laura Gómez']);

        $this->app['events']->listen(
            'eloquent.creating: '.IncidentLog::class,
            fn () => throw new RuntimeException('fallo simulado al escribir el log'),
        );

        try {
            $this->service->reassign($incident, 'Carlos Peña', fake()->name());
            $this->fail('Se esperaba una RuntimeException.');
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertSame('Laura Gómez', $incident->fresh()->assigned_to);
        $this->assertSame(0, IncidentLog::count());
    }

    public function test_adding_a_comment_stores_it_and_leaves_a_trace_in_the_log()
    {
        $incident = Incident::factory()->create();
        $author = fake()->name();

        $comment = $this->service->addComment($incident, $author, fake()->sentence());

        $this->assertTrue($incident->comments()->sole()->is($comment));

        $log = $incident->logs()->sole();
        $this->assertSame(IncidentLogAction::Commented, $log->action);
        $this->assertSame($author, $log->user_name);
        $this->assertSame(['comment_id' => $comment->id], $log->metadata);
    }
}
