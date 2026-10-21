<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeIncidentAssigneeRequest extends FormRequest
{
    /**
     * `assigned_to` es obligatorio que venga en la petición (puede venir vacío, que
     * significa «quitar el responsable»): así un envío sin ese campo no deja sin
     * responsable a la incidencia por accidente.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['present', 'nullable', 'string', 'max:120', Rule::exists('assignees', 'name')],
            'user_name' => ['required', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
