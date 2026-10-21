<?php

namespace Tests\Feature;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Assignee;
use App\Models\Incident;
use App\Models\IncidentComment;
use App\Models\IncidentLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IncidentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validIncidentData(array $overrides = []): array
    {
        return array_merge([
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => IncidentPriority::High->value,
            'requester_name' => fake()->name(),
            'assigned_to' => Assignee::factory()->create()->name,
        ], $overrides);
    }

    // --- Alta -----------------------------------------------------------------

    public function test_a_valid_incident_is_created_open_with_its_creation_log()
    {
        $data = $this->validIncidentData();

        $this->post(route('incidents.store'), $data)->assertRedirect(route('incidents.index'));

        $incident = Incident::sole();
        $this->assertSame($data['title'], $incident->title);
        $this->assertSame(IncidentPriority::High, $incident->priority);
        $this->assertSame(IncidentStatus::Open, $incident->status);

        $log = $incident->logs()->sole();
        $this->assertSame(IncidentLogAction::Created, $log->action);
        $this->assertSame($data['requester_name'], $log->user_name);
    }

    public function test_the_creation_form_cannot_choose_the_initial_status()
    {
        $this->post(route('incidents.store'), $this->validIncidentData([
            'status' => IncidentStatus::Resolved->value,
        ]))->assertRedirect();

        $this->assertSame(IncidentStatus::Open, Incident::sole()->status);
    }

    public function test_an_invalid_incident_is_rejected_with_spanish_messages_and_nothing_is_saved()
    {
        $this->post(route('incidents.store'), [])
            ->assertSessionHasErrors([
                'title' => 'El campo título es obligatorio.',
                'priority' => 'El campo prioridad es obligatorio.',
                'requester_name' => 'El campo solicitante es obligatorio.',
            ]);

        $this->assertSame(0, Incident::count());
        $this->assertSame(0, IncidentLog::count());
    }

    public function test_the_creation_limits_and_priority_values_are_validated()
    {
        $this->post(route('incidents.store'), $this->validIncidentData([
            'title' => str_repeat('a', 161),
            'priority' => 'urgentisima',
        ]))->assertSessionHasErrors([
            'title' => 'El campo título no puede superar los 160 caracteres.',
            'priority' => 'El valor seleccionado para prioridad no es válido.',
        ]);

        $this->assertSame(0, Incident::count());
    }

    public function test_assignee_and_description_are_optional()
    {
        $this->post(route('incidents.store'), $this->validIncidentData([
            'description' => '',
            'assigned_to' => '',
        ]))->assertSessionHasNoErrors();

        $incident = Incident::sole();
        $this->assertNull($incident->description);
        $this->assertNull($incident->assigned_to);
    }

    // --- Listado y filtros ----------------------------------------------------

    public function test_the_list_shows_incidents_newest_first_with_their_allowed_transitions()
    {
        $older = Incident::factory()->create(['created_at' => Carbon::now()->subDays(2)]);
        $newer = Incident::factory()->withStatus(IncidentStatus::InReview)->create([
            'created_at' => Carbon::now()->subDay(),
        ]);

        $this->get(route('incidents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('incidents/index')
                ->has('incidents', 2)
                ->where('incidents.0.id', $newer->id)
                ->where('incidents.0.status', 'in_review')
                ->where('incidents.0.allowed_transitions', ['blocked', 'resolved'])
                ->where('incidents.1.id', $older->id)
                ->where('incidents.1.allowed_transitions', ['in_review']));
    }

    public function test_the_list_can_be_filtered_by_status()
    {
        Incident::factory()->count(2)->create();
        $blocked = Incident::factory()->withStatus(IncidentStatus::Blocked)->create();

        $this->get(route('incidents.index', ['status' => 'blocked']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 1)
                ->where('incidents.0.id', $blocked->id)
                ->where('filters.status', 'blocked'));
    }

    public function test_the_list_can_be_filtered_by_priority()
    {
        Incident::factory()->count(2)->create(['priority' => IncidentPriority::Low]);
        $critical = Incident::factory()->create(['priority' => IncidentPriority::Critical]);

        $this->get(route('incidents.index', ['priority' => 'critical']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 1)
                ->where('incidents.0.id', $critical->id)
                ->where('filters.priority', 'critical'));
    }

    public function test_free_text_search_looks_at_title_description_requester_and_assignee()
    {
        $byTitle = Incident::factory()->create(['title' => 'Falla de la impresora']);
        $byDescription = Incident::factory()->create(['description' => 'La impresora no responde']);
        $byRequester = Incident::factory()->create(['requester_name' => 'Impresora Lopez']);
        $byAssignee = Incident::factory()->create(['assigned_to' => 'Equipo impresora']);
        Incident::factory()->create([
            'title' => 'Otro asunto',
            'description' => 'Sin relación',
            'requester_name' => 'Maria',
            'assigned_to' => 'Pedro',
        ]);

        $expected = collect([$byTitle, $byDescription, $byRequester, $byAssignee])->pluck('id')->sort()->values()->all();

        $this->get(route('incidents.index', ['search' => 'impresora']))
            ->assertInertia(function (Assert $page) use ($expected) {
                $page->has('incidents', 4)->where('filters.search', 'impresora');

                $ids = collect($page->toArray()['props']['incidents'])->pluck('id')->sort()->values()->all();
                $this->assertSame($expected, $ids);
            });
    }

    public function test_search_treats_percent_signs_literally_instead_of_as_wildcards()
    {
        $literal = Incident::factory()->create(['title' => 'Disco al 100% de uso']);
        Incident::factory()->create(['title' => 'Sin ese símbolo']);

        $this->get(route('incidents.index', ['search' => '%']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 1)
                ->where('incidents.0.id', $literal->id));
    }

    public function test_filters_can_be_combined()
    {
        Incident::factory()->withStatus(IncidentStatus::Blocked)->create([
            'priority' => IncidentPriority::Low,
            'title' => 'Red caída',
        ]);
        Incident::factory()->withStatus(IncidentStatus::Open)->create([
            'priority' => IncidentPriority::Critical,
            'title' => 'Red lenta',
        ]);
        $match = Incident::factory()->withStatus(IncidentStatus::Blocked)->create([
            'priority' => IncidentPriority::Critical,
            'title' => 'Red sin acceso',
        ]);

        $this->get(route('incidents.index', ['status' => 'blocked', 'priority' => 'critical', 'search' => 'red']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 1)
                ->where('incidents.0.id', $match->id));
    }

    public function test_the_list_counts_incidents_per_status_including_the_empty_ones()
    {
        Incident::factory()->count(2)->create();
        Incident::factory()->withStatus(IncidentStatus::Blocked)->create();

        $this->get(route('incidents.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('counters.open', 2)
                ->where('counters.in_review', 0)
                ->where('counters.blocked', 1)
                ->where('counters.resolved', 0));
    }

    public function test_the_counters_do_not_depend_on_the_status_filter()
    {
        Incident::factory()->count(2)->create();
        Incident::factory()->withStatus(IncidentStatus::Blocked)->create();

        $this->get(route('incidents.index', ['status' => 'blocked']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 1)
                ->where('counters.open', 2)
                ->where('counters.blocked', 1));
    }

    public function test_the_counters_respect_the_priority_filter()
    {
        Incident::factory()->create(['priority' => IncidentPriority::High]);
        Incident::factory()->count(2)->create(['priority' => IncidentPriority::Low]);

        $this->get(route('incidents.index', ['priority' => 'high']))
            ->assertInertia(fn (Assert $page) => $page->where('counters.open', 1));
    }

    public function test_the_counters_respect_the_text_search_and_treat_wildcards_literally()
    {
        Incident::factory()->create(['title' => 'Disco al 100% de uso']);
        Incident::factory()->count(2)->create(['title' => 'Sin ese símbolo']);

        $this->get(route('incidents.index', ['search' => '%']))
            ->assertInertia(fn (Assert $page) => $page->where('counters.open', 1));
    }

    public function test_unknown_filter_values_are_ignored()
    {
        Incident::factory()->count(2)->create();

        $this->get(route('incidents.index', ['status' => 'inventado', 'priority' => 'inventada']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 2)
                ->where('filters.status', null)
                ->where('filters.priority', null)
                ->where('filters.search', null));
    }

    // --- Responsable y solicitante: opciones y filtros ------------------------

    public function test_the_list_offers_the_assignee_catalog_and_the_existing_requesters_sorted()
    {
        Assignee::factory()->create(['name' => 'Zoe Ramos']);
        Assignee::factory()->create(['name' => 'Ana Torres']);
        Incident::factory()->create(['requester_name' => 'Pedro Díaz']);
        Incident::factory()->create(['requester_name' => 'Marta Ruiz']);
        Incident::factory()->create(['requester_name' => 'Pedro Díaz']);

        $this->get(route('incidents.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('assignees', ['Ana Torres', 'Zoe Ramos'])
                ->where('requesters', ['Marta Ruiz', 'Pedro Díaz']));
    }

    public function test_the_list_can_be_filtered_by_assignee()
    {
        Assignee::factory()->create(['name' => 'Laura Gómez']);
        Assignee::factory()->create(['name' => 'Carlos Peña']);
        $mine = Incident::factory()->create(['assigned_to' => 'Laura Gómez']);
        Incident::factory()->create(['assigned_to' => 'Carlos Peña']);
        Incident::factory()->create(['assigned_to' => null]);

        $this->get(route('incidents.index', ['assignee' => 'Laura Gómez']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 1)
                ->where('incidents.0.id', $mine->id)
                ->where('filters.assignee', 'Laura Gómez'));
    }

    public function test_the_list_can_show_only_the_unassigned_incidents()
    {
        Assignee::factory()->create(['name' => 'Laura Gómez']);
        Incident::factory()->create(['assigned_to' => 'Laura Gómez']);
        $unassigned = Incident::factory()->create(['assigned_to' => null]);

        $this->get(route('incidents.index', ['assignee' => 'unassigned']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 1)
                ->where('incidents.0.id', $unassigned->id)
                ->where('filters.assignee', 'unassigned'));
    }

    public function test_an_assignee_that_is_not_in_the_catalog_is_ignored_as_a_filter()
    {
        Incident::factory()->count(2)->create(['assigned_to' => 'Alguien Externo']);

        $this->get(route('incidents.index', ['assignee' => 'Alguien Externo']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 2)
                ->where('filters.assignee', null));
    }

    public function test_the_list_can_be_filtered_by_requester()
    {
        $match = Incident::factory()->create(['requester_name' => 'Marta Ruiz']);
        Incident::factory()->create(['requester_name' => 'Pedro Díaz']);

        $this->get(route('incidents.index', ['requester' => 'Marta Ruiz']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 1)
                ->where('incidents.0.id', $match->id)
                ->where('filters.requester', 'Marta Ruiz'));
    }

    public function test_a_requester_without_incidents_is_ignored_as_a_filter()
    {
        Incident::factory()->count(2)->create();

        $this->get(route('incidents.index', ['requester' => 'Nadie Conocido']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 2)
                ->where('filters.requester', null));
    }

    public function test_the_counters_respect_the_assignee_and_requester_filters()
    {
        Assignee::factory()->create(['name' => 'Laura Gómez']);
        Incident::factory()->create(['assigned_to' => 'Laura Gómez', 'requester_name' => 'Marta Ruiz']);
        Incident::factory()->create(['assigned_to' => 'Laura Gómez', 'requester_name' => 'Pedro Díaz']);
        Incident::factory()->create(['assigned_to' => null, 'requester_name' => 'Marta Ruiz']);

        $this->get(route('incidents.index', ['assignee' => 'Laura Gómez']))
            ->assertInertia(fn (Assert $page) => $page->where('counters.open', 2));

        $this->get(route('incidents.index', ['requester' => 'Marta Ruiz']))
            ->assertInertia(fn (Assert $page) => $page->where('counters.open', 2));

        $this->get(route('incidents.index', ['assignee' => 'Laura Gómez', 'requester' => 'Marta Ruiz']))
            ->assertInertia(fn (Assert $page) => $page->where('counters.open', 1));
    }

    // --- Paginación -----------------------------------------------------------

    /**
     * Crea `$count` incidencias, de la más reciente a la más antigua, y devuelve sus ids en ese orden.
     *
     * @return array<int, int>
     */
    private function incidentsNewestFirst(int $count, array $attributes = []): array
    {
        return collect(range(1, $count))
            ->map(fn (int $minutesAgo) => Incident::factory()->create([
                ...$attributes,
                'created_at' => Carbon::now()->subMinutes($minutesAgo),
            ])->id)
            ->all();
    }

    public function test_the_list_is_paginated_ten_at_a_time_newest_first()
    {
        $ids = $this->incidentsNewestFirst(12);

        $this->get(route('incidents.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 10)
                ->where('incidents.0.id', $ids[0])
                ->where('incidents.9.id', $ids[9])
                ->where('pagination.current_page', 1)
                ->where('pagination.last_page', 2)
                ->where('pagination.total', 12)
                ->where('pagination.from', 1)
                ->where('pagination.to', 10));

        $this->get(route('incidents.index', ['page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 2)
                ->where('incidents.0.id', $ids[10])
                ->where('pagination.current_page', 2)
                ->where('pagination.from', 11)
                ->where('pagination.to', 12));
    }

    public function test_pagination_applies_after_the_filters_and_the_counters_ignore_it()
    {
        $this->incidentsNewestFirst(12, ['priority' => IncidentPriority::High]);
        Incident::factory()->count(3)->create(['priority' => IncidentPriority::Low]);

        $this->get(route('incidents.index', ['priority' => 'high', 'page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('incidents', 2)
                ->where('pagination.total', 12)
                ->where('counters.open', 12));
    }

    public function test_a_page_past_the_end_redirects_to_the_last_page_keeping_the_filters()
    {
        $this->incidentsNewestFirst(12);
        Incident::factory()->withStatus(IncidentStatus::Blocked)->create();

        $this->get(route('incidents.index', ['page' => 9]))
            ->assertRedirect(route('incidents.index', ['page' => 2]));

        $this->get(route('incidents.index', ['status' => 'blocked', 'page' => 3]))
            ->assertRedirect(route('incidents.index', ['status' => 'blocked', 'page' => 1]));
    }

    public function test_an_invalid_page_number_falls_back_to_the_first_page()
    {
        $this->incidentsNewestFirst(12);

        $this->get(route('incidents.index', ['page' => 'abc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('pagination.current_page', 1));
    }

    // --- Detalle: historial y comentarios -------------------------------------

    public function test_the_detail_shows_the_incident_with_history_and_comments_newest_first()
    {
        $incident = Incident::factory()->withStatus(IncidentStatus::Blocked)->create();
        $oldLog = IncidentLog::factory()->for($incident)->create(['created_at' => Carbon::now()->subHours(3)]);
        $newLog = IncidentLog::factory()->for($incident)->create(['created_at' => Carbon::now()->subHour()]);
        $oldComment = IncidentComment::factory()->for($incident)->create(['created_at' => Carbon::now()->subHours(2)]);
        $newComment = IncidentComment::factory()->for($incident)->create(['created_at' => Carbon::now()->subMinutes(5)]);
        IncidentLog::factory()->create(); // historial de otra incidencia

        $this->get(route('incidents.show', $incident))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('incidents/show')
                ->where('incident.id', $incident->id)
                ->where('incident.allowed_transitions', ['in_review', 'resolved'])
                ->has('logs', 2)
                ->where('logs.0.id', $newLog->id)
                ->where('logs.1.id', $oldLog->id)
                ->has('comments', 2)
                ->where('comments.0.id', $newComment->id)
                ->where('comments.1.id', $oldComment->id));
    }

    public function test_the_detail_of_a_missing_incident_is_a_404()
    {
        $missingId = Incident::factory()->create()->id;
        Incident::query()->whereKey($missingId)->delete();

        $this->get(route('incidents.show', $missingId))->assertNotFound();
    }

    // --- Cambio de estado -----------------------------------------------------

    public function test_a_valid_status_change_is_saved_with_its_log()
    {
        $incident = Incident::factory()->create();
        $actor = fake()->name();
        $reason = fake()->sentence();

        $this->patch(route('incidents.status.update', $incident), [
            'status' => 'in_review',
            'user_name' => $actor,
            'note' => $reason,
        ])->assertRedirect();

        $this->assertSame(IncidentStatus::InReview, $incident->fresh()->status);

        $log = $incident->logs()->sole();
        $this->assertSame(IncidentLogAction::StatusChanged, $log->action);
        $this->assertSame('open', $log->old_value);
        $this->assertSame('in_review', $log->new_value);
        $this->assertSame($actor, $log->user_name);
        $this->assertSame(['reason' => $reason], $log->metadata);
    }

    public function test_an_invalid_transition_over_http_returns_an_error_on_status_and_changes_nothing()
    {
        $incident = Incident::factory()->create();

        $this->patch(route('incidents.status.update', $incident), [
            'status' => 'resolved',
            'user_name' => fake()->name(),
        ])->assertSessionHasErrors(['status' => 'No se puede pasar de «Abierta» a «Resuelta».']);

        $this->assertSame(IncidentStatus::Open, $incident->fresh()->status);
        $this->assertSame(0, IncidentLog::count());
    }

    public function test_the_status_change_requires_a_known_status_and_the_actor_name()
    {
        $incident = Incident::factory()->create();

        $this->patch(route('incidents.status.update', $incident), ['status' => 'inventado'])
            ->assertSessionHasErrors([
                'status' => 'El valor seleccionado para estado no es válido.',
                'user_name' => 'El campo nombre es obligatorio.',
            ]);

        $this->assertSame(0, IncidentLog::count());
    }

    public function test_reopening_over_http_requires_a_reason_and_leaves_an_explicit_log()
    {
        $incident = Incident::factory()->withStatus(IncidentStatus::Resolved)->create();
        $payload = ['status' => 'in_review', 'user_name' => fake()->name()];

        $this->patch(route('incidents.status.update', $incident), $payload)
            ->assertSessionHasErrors(['note' => 'Indica el motivo de la reapertura.']);
        $this->assertSame(IncidentStatus::Resolved, $incident->fresh()->status);

        $this->patch(route('incidents.status.update', $incident), $payload + ['note' => fake()->sentence()])
            ->assertSessionHasNoErrors();

        $this->assertSame(IncidentStatus::InReview, $incident->fresh()->status);
        $this->assertSame(IncidentLogAction::Reopened, $incident->logs()->sole()->action);
    }

    // --- Responsable ----------------------------------------------------------

    public function test_the_creation_form_only_accepts_assignees_from_the_catalog()
    {
        $this->post(route('incidents.store'), $this->validIncidentData(['assigned_to' => 'Alguien Externo']))
            ->assertSessionHasErrors(['assigned_to' => 'El valor seleccionado para responsable no es válido.']);

        $this->assertSame(0, Incident::count());
    }

    public function test_the_creation_form_stores_the_chosen_assignee()
    {
        $assignee = Assignee::factory()->create();

        $this->post(route('incidents.store'), $this->validIncidentData(['assigned_to' => $assignee->name]))
            ->assertSessionHasNoErrors();

        $this->assertSame($assignee->name, Incident::sole()->assigned_to);
    }

    public function test_the_detail_offers_the_assignee_catalog_for_reassigning()
    {
        Assignee::factory()->create(['name' => 'Zoe Ramos']);
        Assignee::factory()->create(['name' => 'Ana Torres']);
        $incident = Incident::factory()->create();

        $this->get(route('incidents.show', $incident))
            ->assertInertia(fn (Assert $page) => $page->where('assignees', ['Ana Torres', 'Zoe Ramos']));
    }

    public function test_an_incident_can_be_reassigned_and_the_change_is_logged()
    {
        $incident = Incident::factory()->create(['assigned_to' => null]);
        $assignee = Assignee::factory()->create();
        $actor = fake()->name();
        $reason = fake()->sentence();

        $this->patch(route('incidents.assignee.update', $incident), [
            'assigned_to' => $assignee->name,
            'user_name' => $actor,
            'note' => $reason,
        ])->assertRedirect();

        $this->assertSame($assignee->name, $incident->fresh()->assigned_to);

        $log = $incident->logs()->sole();
        $this->assertSame(IncidentLogAction::Assigned, $log->action);
        $this->assertNull($log->old_value);
        $this->assertSame($assignee->name, $log->new_value);
        $this->assertSame($actor, $log->user_name);
        $this->assertSame(['reason' => $reason], $log->metadata);
    }

    public function test_an_empty_assignee_removes_the_assignment()
    {
        $assignee = Assignee::factory()->create();
        $incident = Incident::factory()->create(['assigned_to' => $assignee->name]);

        $this->patch(route('incidents.assignee.update', $incident), [
            'assigned_to' => '',
            'user_name' => fake()->name(),
        ])->assertSessionHasNoErrors();

        $this->assertNull($incident->fresh()->assigned_to);
    }

    public function test_reassigning_without_the_assignee_field_does_not_remove_the_assignment_by_accident()
    {
        $assignee = Assignee::factory()->create();
        $incident = Incident::factory()->create(['assigned_to' => $assignee->name]);

        $this->patch(route('incidents.assignee.update', $incident), ['user_name' => fake()->name()])
            ->assertSessionHasErrors(['assigned_to' => 'El campo responsable debe estar presente.']);

        $this->assertSame($assignee->name, $incident->fresh()->assigned_to);
        $this->assertSame(0, IncidentLog::count());
    }

    public function test_reassigning_only_accepts_assignees_from_the_catalog_and_needs_the_actor_name()
    {
        $incident = Incident::factory()->create(['assigned_to' => null]);

        $this->patch(route('incidents.assignee.update', $incident), ['assigned_to' => 'Alguien Externo'])
            ->assertSessionHasErrors([
                'assigned_to' => 'El valor seleccionado para responsable no es válido.',
                'user_name' => 'El campo nombre es obligatorio.',
            ]);

        $this->assertNull($incident->fresh()->assigned_to);
        $this->assertSame(0, IncidentLog::count());
    }

    public function test_reassigning_to_the_same_person_returns_an_error_on_the_assignee()
    {
        $assignee = Assignee::factory()->create();
        $incident = Incident::factory()->create(['assigned_to' => $assignee->name]);

        $this->patch(route('incidents.assignee.update', $incident), [
            'assigned_to' => $assignee->name,
            'user_name' => fake()->name(),
        ])->assertSessionHasErrors(['assigned_to' => "La incidencia ya está asignada a «{$assignee->name}»."]);

        $this->assertSame(0, IncidentLog::count());
    }

    // --- Comentarios ----------------------------------------------------------

    public function test_a_valid_comment_is_saved_and_traced()
    {
        $incident = Incident::factory()->create();
        $author = fake()->name();
        $body = fake()->sentence();

        $this->post(route('incidents.comments.store', $incident), [
            'author_name' => $author,
            'body' => $body,
        ])->assertRedirect();

        $comment = $incident->comments()->sole();
        $this->assertSame($author, $comment->author_name);
        $this->assertSame($body, $comment->body);
        $this->assertSame(IncidentLogAction::Commented, $incident->logs()->sole()->action);
    }

    public function test_an_invalid_comment_is_rejected_with_spanish_messages()
    {
        $incident = Incident::factory()->create();

        $this->post(route('incidents.comments.store', $incident), [])
            ->assertSessionHasErrors([
                'author_name' => 'El campo autor es obligatorio.',
                'body' => 'El campo comentario es obligatorio.',
            ]);

        $this->assertSame(0, IncidentComment::count());
        $this->assertSame(0, IncidentLog::count());
    }
}
