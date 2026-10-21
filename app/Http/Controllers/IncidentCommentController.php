<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentCommentRequest;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class IncidentCommentController extends Controller
{
    /**
     * Añade un comentario operativo a la incidencia.
     */
    public function store(StoreIncidentCommentRequest $request, Incident $incident, IncidentService $service): RedirectResponse
    {
        $data = $request->validated();

        $service->addComment($incident, $data['author_name'], $data['body']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comentario añadido.']);

        return back();
    }
}
