<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeIncidentAssigneeRequest;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class IncidentAssigneeController extends Controller
{
    /**
     * Cambia el responsable de una incidencia, o se lo quita si llega vacío.
     */
    public function update(ChangeIncidentAssigneeRequest $request, Incident $incident, IncidentService $service): RedirectResponse
    {
        $data = $request->validated();
        $assignee = $data['assigned_to'];

        $service->reassign($incident, $assignee, $data['user_name'], $data['note'] ?? null);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $assignee === null
                ? 'Se quitó el responsable de la incidencia.'
                : "Responsable actualizado a «{$assignee}».",
        ]);

        return back();
    }
}
