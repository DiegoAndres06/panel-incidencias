<?php

namespace App\Enums;

/**
 * Tipos de acción que quedan registrados en la trazabilidad de una incidencia.
 */
enum IncidentLogAction: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case Reopened = 'reopened';
    case Commented = 'commented';
    case Assigned = 'assigned';

    /**
     * Texto en español para mensajes (por ejemplo, los del diagnóstico).
     */
    public function label(): string
    {
        return match ($this) {
            self::Created => 'creación',
            self::StatusChanged => 'cambio de estado',
            self::Reopened => 'reapertura',
            self::Commented => 'comentario',
            self::Assigned => 'cambio de responsable',
        };
    }

    /**
     * Indica si la acción deja la incidencia en un estado concreto (`new_value`).
     * Los comentarios y los cambios de responsable no cambian el estado.
     */
    public function affectsStatus(): bool
    {
        return match ($this) {
            self::Created, self::StatusChanged, self::Reopened => true,
            self::Commented, self::Assigned => false,
        };
    }

    /**
     * Valores de las acciones que afectan al estado, para filtrar consultas.
     *
     * @return array<int, string>
     */
    public static function statusActionValues(): array
    {
        $values = [];

        foreach (self::cases() as $action) {
            if ($action->affectsStatus()) {
                $values[] = $action->value;
            }
        }

        return $values;
    }
}
