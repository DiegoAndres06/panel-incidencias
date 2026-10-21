<?php

namespace App\Http\Requests;

use App\Enums\IncidentPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(IncidentPriority::class)],
            'requester_name' => ['required', 'string', 'max:120'],
            // El responsable se elige del catálogo, no se escribe libremente.
            'assigned_to' => ['nullable', 'string', 'max:120', Rule::exists('assignees', 'name')],
        ];
    }
}
