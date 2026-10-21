<?php

namespace App\Enums;

/**
 * Estados de una incidencia y mapa de transiciones permitidas.
 *
 * Es la única fuente de verdad de los estados: el resto del código nunca debe
 * escribir el valor como texto suelto.
 */
enum IncidentStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Blocked = 'blocked';
    case Resolved = 'resolved';

    /**
     * Texto en español para mostrar en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::InReview => 'En revisión',
            self::Blocked => 'Bloqueada',
            self::Resolved => 'Resuelta',
        };
    }

    /**
     * Estados a los que se puede pasar desde este.
     *
     * Una incidencia resuelta no vuelve a abierta: se reabre pasando a "en revisión",
     * lo que queda registrado como reapertura.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::InReview],
            self::InReview => [self::Blocked, self::Resolved],
            self::Blocked => [self::InReview, self::Resolved],
            self::Resolved => [self::InReview],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Indica si pasar a $to desde este estado es una reapertura (resuelta → en revisión).
     */
    public function isReopening(self $to): bool
    {
        return $this === self::Resolved && $to === self::InReview;
    }

    /**
     * Pares valor → texto, para selects y para enviar al frontend.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
