import type { LucideIcon } from 'lucide-react';
import { ListFilter } from 'lucide-react';
import { INCIDENT_STATUSES, STATUS_CONFIG } from '@/lib/incidents/config';
import { applyIncidentFilters } from '@/lib/incidents/visit';
import { cn } from '@/lib/utils';
import type { IncidentListFilters, IncidentStatusCounters } from '@/types';

type CounterProps = {
    label: string;
    count: number;
    icon: LucideIcon;
    active: boolean;
    className: string;
    onClick: () => void;
};

function Counter({
    label,
    count,
    icon: Icon,
    active,
    className,
    onClick,
}: CounterProps) {
    return (
        <button
            type="button"
            aria-pressed={active}
            onClick={onClick}
            className={cn(
                'flex items-center justify-between gap-2 rounded-xl border px-4 py-3 text-left transition-shadow outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                className,
                active && 'ring-2 ring-ring',
            )}
        >
            <span className="flex items-center gap-2 text-sm font-medium">
                <Icon className="size-4" aria-hidden="true" />
                {label}
            </span>
            <span className="text-2xl font-semibold tabular-nums">{count}</span>
        </button>
    );
}

// Contador de incidencias por estado. Cada uno es un botón que filtra el listado por ese
// estado; el activo queda marcado. Respetan los filtros de prioridad y de texto, pero no el
// de estado, así se ve cuántas hay en los demás estados mientras se filtra por uno.
export function StatusCounters({
    counters,
    filters,
}: {
    counters: IncidentStatusCounters;
    filters: IncidentListFilters;
}) {
    const total = INCIDENT_STATUSES.reduce(
        (sum, status) => sum + counters[status],
        0,
    );

    return (
        <nav
            aria-label="Incidencias por estado"
            className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5"
        >
            <Counter
                label="Todas"
                count={total}
                icon={ListFilter}
                active={filters.status === null}
                className="bg-card text-card-foreground"
                onClick={() =>
                    applyIncidentFilters({ ...filters, status: null })
                }
            />
            {INCIDENT_STATUSES.map((status) => (
                <Counter
                    key={status}
                    label={STATUS_CONFIG[status].label}
                    count={counters[status]}
                    icon={STATUS_CONFIG[status].icon}
                    active={filters.status === status}
                    className={STATUS_CONFIG[status].badgeClass}
                    onClick={() => applyIncidentFilters({ ...filters, status })}
                />
            ))}
        </nav>
    );
}
