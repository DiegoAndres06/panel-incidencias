<?php

namespace App\Enums;

/**
 * Prioridades de una incidencia, de menor a mayor urgencia.
 */
enum IncidentPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    /**
     * Texto en español para mostrar en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
            self::Critical => 'Crítica',
        };
    }

    /**
     * Pares valor → texto, para selects y para enviar al frontend.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $priority) {
            $options[$priority->value] = $priority->label();
        }

        return $options;
    }
}
