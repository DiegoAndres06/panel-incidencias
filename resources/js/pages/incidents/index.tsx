import { Head, usePage } from '@inertiajs/react';
import { IncidentCards } from '@/components/incidents/incident-cards';
import { IncidentFilters } from '@/components/incidents/incident-filters';
import { IncidentFormDialog } from '@/components/incidents/incident-form-dialog';
import { IncidentPaginationBar } from '@/components/incidents/incident-pagination';
import { IncidentTable } from '@/components/incidents/incident-table';
import { StatusCounters } from '@/components/incidents/status-counters';
import { index } from '@/routes/incidents';
import type {
    Incident,
    IncidentListFilters,
    IncidentPagination,
    IncidentStatusCounters,
} from '@/types';

type Props = {
    incidents: Incident[];
    filters: IncidentListFilters;
    counters: IncidentStatusCounters;
    pagination: IncidentPagination;
    // Opciones de los desplegables: el catálogo de responsables y los solicitantes existentes.
    assignees: string[];
    requesters: string[];
};

export default function IncidentsIndex({
    incidents,
    filters,
    counters,
    pagination,
    assignees,
    requesters,
}: Props) {
    const { name } = usePage().props;

    const hasFilters = Boolean(
        filters.status ||
        filters.priority ||
        filters.search ||
        filters.assignee ||
        filters.requester,
    );

    return (
        <>
            <Head title="Incidencias" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {name}
                        </h1>
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            Consulta las incidencias operativas, registra
                            nuevas, cambia su estado y revisa el historial de
                            cada cambio.
                        </p>
                    </div>
                    <IncidentFormDialog assignees={assignees} />
                </header>

                <StatusCounters counters={counters} filters={filters} />

                <IncidentFilters
                    filters={filters}
                    assignees={assignees}
                    requesters={requesters}
                />

                {incidents.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {hasFilters
                            ? 'Ninguna incidencia coincide con los filtros.'
                            : 'No hay incidencias registradas.'}
                    </p>
                ) : (
                    <>
                        <IncidentTable incidents={incidents} />
                        <IncidentCards incidents={incidents} />
                        <IncidentPaginationBar
                            pagination={pagination}
                            filters={filters}
                        />
                    </>
                )}
            </div>
        </>
    );
}

IncidentsIndex.layout = {
    breadcrumbs: [{ title: 'Incidencias', href: index() }],
};
