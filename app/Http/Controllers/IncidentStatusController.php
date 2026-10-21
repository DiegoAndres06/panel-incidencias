<?php

namespace App\Http\Controllers;

use App\Enums\IncidentStatus;
use App\Http\Requests\ChangeIncidentStatusRequest;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class IncidentStatusController extends Controller
{
    /**
     * Cambia el estado de una incidencia. Si la transición no es válida, el servicio
     * lanza una excepción de validación y el usuario ve el error en el formulario.
     */
    public function update(ChangeIncidentStatusRequest $request, Incident $incident, IncidentService $service): RedirectResponse
    {
        $data = $request->validated();
        $status = IncidentStatus::from($data['status']);

        $service->changeStatus($incident, $status, $data['user_name'], $data['note'] ?? null);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Estado actualizado a «{$status->label()}».",
        ]);

        return back();
    }
}
