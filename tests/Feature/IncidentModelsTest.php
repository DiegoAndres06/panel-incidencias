<?php

namespace Tests\Feature;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Assignee;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\IncidentLog;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IncidentModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_incident_defaults_to_open_status_and_medium_priority()
    {
        $incident = Incident::create([
            'title' => fake()->sentence(),
            'requester_name' => fake()->name(),
        ])->fresh();

        $this->assertSame(IncidentStatus::Open, $incident->status);
        $this->assertSame(IncidentPriority::Medium, $incident->priority);
    }

    public function test_status_and_priority_are_cast_to_enums()
    {
        $incident = Incident::factory()
            ->withStatus(IncidentStatus::Blocked)
            ->create(['priority' => IncidentPriority::Critical])
            ->fresh();

        $this->assertSame(IncidentStatus::Blocked, $incident->status);
        $this->assertSame(IncidentPriority::Critical, $incident->priority);
    }

    public function test_an_incident_owns_its_logs_and_comments()
    {
        $incident = Incident::factory()->create();

        $log = $incident->logs()->create([
            'action' => IncidentLogAction::Created,
            'new_value' => IncidentStatus::Open->value,
        ]);
        $comment = $incident->comments()->create([
            'author_name' => fake()->name(),
            'body' => fake()->sentence(),
        ]);

        $this->assertTrue($incident->logs->contains($log));
        $this->assertTrue($incident->comments->contains($comment));
        $this->assertTrue($log->incident->is($incident));
        $this->assertTrue($comment->incident->is($incident));
    }

    public function test_the_incident_id_cannot_be_mass_assigned_on_logs_and_comments()
    {
        $incident = Incident::factory()->create();

        $log = new IncidentLog(['incident_id' => $incident->id, 'action' => IncidentLogAction::Created]);
        $comment = new IncidentComment(['incident_id' => $incident->id, 'body' => fake()->sentence()]);

        $this->assertNull($log->incident_id);
        $this->assertNull($comment->incident_id);
    }

    public function test_a_log_stores_its_metadata_as_an_array_and_its_action_as_an_enum()
    {
        $reason = fake()->sentence();
        $log = IncidentLog::factory()->create([
            'action' => IncidentLogAction::Reopened,
            'metadata' => ['reason' => $reason],
        ])->fresh();

        $this->assertSame(IncidentLogAction::Reopened, $log->action);
        $this->assertSame(['reason' => $reason], $log->metadata);
    }

    public function test_a_log_has_a_creation_date_but_no_update_date()
    {
        $log = IncidentLog::factory()->create()->fresh();

        $this->assertNotNull($log->created_at);
        $this->assertFalse(Schema::hasColumn('incident_logs', 'updated_at'));
    }

    public function test_deleting_an_incident_deletes_its_logs_and_comments()
    {
        $incident = Incident::factory()->create();
        IncidentLog::factory()->for($incident)->create();
        IncidentComment::factory()->for($incident)->create();

        $incident->delete();

        $this->assertSame(0, IncidentLog::count());
        $this->assertSame(0, IncidentComment::count());
    }

    public function test_a_log_cannot_point_to_an_incident_that_does_not_exist()
    {
        $missingId = Incident::factory()->create()->id;
        Incident::query()->whereKey($missingId)->delete();

        $this->expectException(QueryException::class);

        IncidentLog::factory()->create(['incident_id' => $missingId]);
    }

    public function test_assignee_names_are_unique()
    {
        $name = fake()->name();
        Assignee::factory()->create(['name' => $name]);

        $this->expectException(QueryException::class);

        Assignee::factory()->create(['name' => $name]);
    }

    public function test_the_assignee_and_requester_filters_have_their_own_indexes()
    {
        $indexedColumns = collect(Schema::getIndexes('incidents'))
            ->map(fn (array $index) => $index['columns'])
            ->all();

        $this->assertContains(['assigned_to', 'created_at'], $indexedColumns);
        $this->assertContains(['requester_name', 'created_at'], $indexedColumns);
    }

    public function test_the_listing_filters_have_an_index_that_ends_in_the_creation_date()
    {
        $indexedColumns = collect(Schema::getIndexes('incidents'))
            ->map(fn (array $index) => $index['columns'])
            ->all();

        $this->assertContains(['status', 'created_at'], $indexedColumns);
        $this->assertContains(['priority', 'created_at'], $indexedColumns);
        $this->assertContains(['created_at'], $indexedColumns);
    }
}
