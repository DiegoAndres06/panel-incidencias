<?php

namespace Tests\Feature;

use App\Models\Incident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Páginas de incidencias. La aplicación no usa autenticación: cualquier visitante puede entrar.
 */
class IncidentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_see_the_incident_list_page_without_logging_in()
    {
        $this->get(route('incidents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('incidents/index'));
    }

    public function test_the_root_url_shows_the_incident_list_page()
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('incidents/index'));
    }

    public function test_visitors_see_the_detail_page_of_the_requested_incident()
    {
        $incident = Incident::factory()->create();

        $this->get(route('incidents.show', $incident))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('incidents/show')
                ->where('incident.id', $incident->id));
    }
}
