<?php

namespace App\Support;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;

/**
 * Filtros del listado de incidencias. `null` significa "sin filtrar por eso".
 */
final readonly class IncidentFilters
{
    /** Valor especial del filtro de responsable: solo las incidencias sin responsable. */
    public const UNASSIGNED = 'unassigned';

    public function __construct(
        public ?IncidentStatus $status = null,
        public ?IncidentPriority $priority = null,
        public ?string $search = null,
        public ?string $assignee = null,
        public ?string $requester = null,
    ) {}

    /**
     * Los mismos filtros sin el de estado. Con ellos se calculan los contadores por estado,
     * que no deben depender del estado que se esté filtrando.
     */
    public function withoutStatus(): self
    {
        return new self(null, $this->priority, $this->search, $this->assignee, $this->requester);
    }

    /**
     * Valores tal como los recibe el frontend.
     *
     * @return array{status: ?string, priority: ?string, search: ?string, assignee: ?string, requester: ?string}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'search' => $this->search,
            'assignee' => $this->assignee,
            'requester' => $this->requester,
        ];
    }

    /**
     * Solo los filtros aplicados, para construir una URL.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter($this->toArray(), fn (?string $value) => $value !== null);
    }
}
