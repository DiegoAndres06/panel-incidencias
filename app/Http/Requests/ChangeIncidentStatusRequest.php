<?php

namespace App\Http\Requests;

use App\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeIncidentStatusRequest extends FormRequest
{
    /**
     * Valida el formato. Que la transición sea válida lo decide `IncidentService`,
     * porque depende del estado actual de la incidencia en la base de datos.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(IncidentStatus::class)],
            'user_name' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
