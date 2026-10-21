<?php

namespace App\Http\Requests;

use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use App\Models\Assignee;
use App\Models\Incident;
use App\Support\IncidentFilters;
use Illuminate\Foundation\Http\FormRequest;

class IncidentIndexRequest extends FormRequest
{
    private const MAX_SEARCH_LENGTH = 100;

    private const MAX_NAME_LENGTH = 120;

    /**
     * Los filtros del listado son opcionales y se leen con tolerancia: un valor
     * desconocido se ignora en lugar de romper la pantalla.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function filters(): IncidentFilters
    {
        $search = mb_substr($this->string('search')->trim()->value(), 0, self::MAX_SEARCH_LENGTH);

        return new IncidentFilters(
            status: IncidentStatus::tryFrom($this->string('status')->value()),
            priority: IncidentPriority::tryFrom($this->string('priority')->value()),
            search: $search === '' ? null : $search,
            assignee: $this->knownAssignee(),
            requester: $this->knownRequester(),
        );
    }

    /**
     * El responsable filtrado, tal como está en el catálogo (con su capitalización), o
     * `unassigned`. Si no existe se ignora.
     */
    private function knownAssignee(): ?string
    {
        $assignee = mb_substr($this->string('assignee')->trim()->value(), 0, self::MAX_NAME_LENGTH);

        if ($assignee === '') {
            return null;
        }

        if ($assignee === IncidentFilters::UNASSIGNED) {
            return $assignee;
        }

        return Assignee::query()->where('name', $assignee)->value('name');
    }

    /**
     * El solicitante filtrado, tal como está guardado, o null si ninguna incidencia lo tiene.
     */
    private function knownRequester(): ?string
    {
        $requester = mb_substr($this->string('requester')->trim()->value(), 0, self::MAX_NAME_LENGTH);

        if ($requester === '') {
            return null;
        }

        return Incident::query()->where('requester_name', $requester)->value('requester_name');
    }
}
