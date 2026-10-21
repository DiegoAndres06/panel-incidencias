<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncidentIndexRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Resources\IncidentCommentResource;
use App\Http\Resources\IncidentLogResource;
use App\Http\Resources\IncidentResource;
use App\Models\Assignee;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    /** Incidencias por página en el listado. */
    private const PER_PAGE = 10;

    /**
     * Pantalla principal: listado paginado de incidencias, de la más reciente a la más
     * antigua, con filtros opcionales por estado, prioridad, texto libre, responsable y
     * solicitante.
     *
     * Si se pide una página que ya no existe (por ejemplo, tras resolver la única incidencia
     * de la última página), redirige a la última que sí existe conservando los filtros.
     */
    public function index(IncidentIndexRequest $request): Response|RedirectResponse
    {
        $filters = $request->filters();

        $incidents = Incident::query()
            ->filtered($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        if ($incidents->isEmpty() && $incidents->currentPage() > 1) {
            return redirect()->route('incidents.index', [
                ...$filters->toQuery(),
                'page' => $incidents->lastPage(),
            ]);
        }

        return Inertia::render('incidents/index', [
            'incidents' => IncidentResource::collection($incidents->getCollection())->resolve(),
            'pagination' => [
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
                'per_page' => $incidents->perPage(),
                'total' => $incidents->total(),
                'from' => $incidents->firstItem(),
                'to' => $incidents->lastItem(),
            ],
            'filters' => $filters->toArray(),
            'counters' => Incident::countsByStatus($filters),
            // Opciones de los desplegables de filtro y de alta.
            'assignees' => Assignee::names(),
            'requesters' => Incident::query()->distinct()->orderBy('requester_name')->pluck('requester_name')->all(),
        ]);
    }

    /**
     * Da de alta una incidencia.
     */
    public function store(StoreIncidentRequest $request, IncidentService $service): RedirectResponse
    {
        $service->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Incidencia creada.']);

        return redirect()->route('incidents.index');
    }

    /**
     * Detalle de una incidencia con su historial de cambios y sus comentarios,
     * lo más reciente primero.
     */
    public function show(Incident $incident): Response
    {
        $logs = $incident->logs()->latest('created_at')->latest('id')->get();
        $comments = $incident->comments()->latest('created_at')->latest('id')->get();

        return Inertia::render('incidents/show', [
            'incident' => (new IncidentResource($incident))->resolve(),
            'logs' => IncidentLogResource::collection($logs)->resolve(),
            'comments' => IncidentCommentResource::collection($comments)->resolve(),
            'assignees' => Assignee::names(),
        ]);
    }
}
