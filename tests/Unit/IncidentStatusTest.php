<?php

namespace Tests\Unit;

use App\Enums\IncidentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IncidentStatusTest extends TestCase
{
    /**
     * Matriz completa de transiciones: cada par (origen, destino) con lo que se espera.
     *
     * @return array<string, array{IncidentStatus, IncidentStatus, bool}>
     */
    public static function transitionMatrix(): array
    {
        $allowed = [
            'open' => ['in_review'],
            'in_review' => ['blocked', 'resolved'],
            'blocked' => ['in_review', 'resolved'],
            'resolved' => ['in_review'],
        ];

        $cases = [];

        foreach (IncidentStatus::cases() as $from) {
            foreach (IncidentStatus::cases() as $to) {
                $expected = in_array($to->value, $allowed[$from->value], true);

                $cases["{$from->value} -> {$to->value}"] = [$from, $to, $expected];
            }
        }

        return $cases;
    }

    #[DataProvider('transitionMatrix')]
    public function test_it_only_allows_the_documented_transitions(IncidentStatus $from, IncidentStatus $to, bool $expected)
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    public function test_allowed_transitions_agree_with_can_transition_to()
    {
        foreach (IncidentStatus::cases() as $from) {
            foreach (IncidentStatus::cases() as $to) {
                $this->assertSame(
                    in_array($to, $from->allowedTransitions(), true),
                    $from->canTransitionTo($to),
                );
            }
        }
    }

    public function test_a_resolved_incident_cannot_go_back_to_open_directly()
    {
        $this->assertFalse(IncidentStatus::Resolved->canTransitionTo(IncidentStatus::Open));
    }

    public function test_only_resolved_to_in_review_is_a_reopening()
    {
        foreach (IncidentStatus::cases() as $from) {
            foreach (IncidentStatus::cases() as $to) {
                $expected = $from === IncidentStatus::Resolved && $to === IncidentStatus::InReview;

                $this->assertSame($expected, $from->isReopening($to));
            }
        }
    }

    public function test_every_status_has_a_spanish_label()
    {
        $this->assertSame('Abierta', IncidentStatus::Open->label());
        $this->assertSame('En revisión', IncidentStatus::InReview->label());
        $this->assertSame('Bloqueada', IncidentStatus::Blocked->label());
        $this->assertSame('Resuelta', IncidentStatus::Resolved->label());
    }

    public function test_options_map_each_value_to_its_label()
    {
        $options = IncidentStatus::options();

        $this->assertCount(count(IncidentStatus::cases()), $options);

        foreach (IncidentStatus::cases() as $status) {
            $this->assertSame($status->label(), $options[$status->value]);
        }
    }
}
