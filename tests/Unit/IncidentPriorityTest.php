<?php

namespace Tests\Unit;

use App\Enums\IncidentPriority;
use PHPUnit\Framework\TestCase;

class IncidentPriorityTest extends TestCase
{
    public function test_every_priority_has_a_spanish_label()
    {
        $this->assertSame('Baja', IncidentPriority::Low->label());
        $this->assertSame('Media', IncidentPriority::Medium->label());
        $this->assertSame('Alta', IncidentPriority::High->label());
        $this->assertSame('Crítica', IncidentPriority::Critical->label());
    }

    public function test_options_map_each_value_to_its_label()
    {
        $options = IncidentPriority::options();

        $this->assertCount(count(IncidentPriority::cases()), $options);

        foreach (IncidentPriority::cases() as $priority) {
            $this->assertSame($priority->label(), $options[$priority->value]);
        }
    }

    public function test_values_match_the_ones_the_frontend_expects()
    {
        $this->assertSame(
            ['low', 'medium', 'high', 'critical'],
            array_map(fn (IncidentPriority $priority) => $priority->value, IncidentPriority::cases()),
        );
    }
}
