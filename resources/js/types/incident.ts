export type IncidentStatus = 'open' | 'in_review' | 'blocked' | 'resolved';

export type IncidentPriority = 'low' | 'medium' | 'high' | 'critical';

export type IncidentLogAction =
    | 'created'
    | 'status_changed'
    | 'reopened'
    | 'commented'
    | 'assigned';

export type Incident = {
    id: number;
    title: string;
    description: string | null;
    status: IncidentStatus;
    priority: IncidentPriority;
    requester_name: string;
    assigned_to: string | null;
    created_at: string;
    updated_at: string;
    // Estados a los que se puede pasar desde el actual; los decide el servidor.
    allowed_transitions: IncidentStatus[];
};

export type IncidentLog = {
    id: number;
    incident_id: number;
    action: IncidentLogAction;
    old_value: string | null;
    new_value: string | null;
    user_name: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
};

export type IncidentComment = {
    id: number;
    incident_id: number;
    author_name: string;
    body: string;
    created_at: string;
};

// Cuántas incidencias hay en cada estado (los cuatro estados siempre están presentes).
export type IncidentStatusCounters = Record<IncidentStatus, number>;

// Filtros aplicados al listado; `null` significa "sin filtrar".
export type IncidentListFilters = {
    status: IncidentStatus | null;
    priority: IncidentPriority | null;
    search: string | null;
    // Nombre del responsable, o `unassigned` para las que no tienen ninguno.
    assignee: string | null;
    requester: string | null;
};

// Datos de la página actual del listado paginado.
export type IncidentPagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};
