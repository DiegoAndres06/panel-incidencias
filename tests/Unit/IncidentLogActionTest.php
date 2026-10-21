<?php

namespace Tests\Unit;

use App\Enums\IncidentLogAction;
use PHPUnit\Framework\TestCase;

class IncidentLogActionTest extends TestCase
{
    public function test_values_match_the_ones_the_frontend_expects()
    {
        $this->assertSame(
            ['created', 'status_changed', 'reopened', 'commented', 'assigned'],
            array_map(fn (IncidentLogAction $action) => $action->value, IncidentLogAction::cases()),
        );
    }

    public function test_only_creation_status_changes_and_reopenings_affect_the_status()
    {
        $this->assertSame(
            ['created', 'status_changed', 'reopened'],
            IncidentLogAction::statusActionValues(),
        );

        $this->assertTrue(IncidentLogAction::Created->affectsStatus());
        $this->assertTrue(IncidentLogAction::StatusChanged->affectsStatus());
        $this->assertTrue(IncidentLogAction::Reopened->affectsStatus());
        $this->assertFalse(IncidentLogAction::Commented->affectsStatus());
        $this->assertFalse(IncidentLogAction::Assigned->affectsStatus());
    }

    public function test_every_action_has_a_spanish_label()
    {
        $this->assertSame('creación', IncidentLogAction::Created->label());
        $this->assertSame('cambio de estado', IncidentLogAction::StatusChanged->label());
        $this->assertSame('reapertura', IncidentLogAction::Reopened->label());
        $this->assertSame('comentario', IncidentLogAction::Commented->label());
        $this->assertSame('cambio de responsable', IncidentLogAction::Assigned->label());
    }

    public function test_every_action_fits_in_the_action_column()
    {
        foreach (IncidentLogAction::cases() as $action) {
            $this->assertLessThanOrEqual(40, strlen($action->value));
        }
    }
}
