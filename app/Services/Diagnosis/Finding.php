<?php

namespace App\Services\Diagnosis;

/**
 * Una inconsistencia encontrada en una incidencia.
 */
final readonly class Finding
{
    /**
     * @param  string  $summary  Qué pasa, en una frase y con el título de la incidencia.
     * @param  string  $explanation  Qué se haría para corregirlo, o por qué no se corrige solo.
     * @param  array<string, mixed>  $context  Datos que necesita la reparación.
     */
    public function __construct(
        public int $incidentId,
        public string $summary,
        public Resolution $resolution,
        public string $explanation,
        public array $context = [],
    ) {}

    public function isAutomatic(): bool
    {
        return $this->resolution === Resolution::Automatic;
    }
}
