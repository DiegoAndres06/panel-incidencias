import { Search, X } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    INCIDENT_PRIORITIES,
    INCIDENT_STATUSES,
    PRIORITY_CONFIG,
    STATUS_CONFIG,
    UNASSIGNED_FILTER,
} from '@/lib/incidents/config';
import { applyIncidentFilters } from '@/lib/incidents/visit';
import type {
    IncidentListFilters,
    IncidentPriority,
    IncidentStatus,
} from '@/types';

// Valor del selector para "sin filtro": Radix no admite una opción con valor vacío.
const ALL = 'all';

type Props = {
    filters: IncidentListFilters;
    assignees: string[];
    requesters: string[];
};

// Barra de filtros del listado: texto libre, estado, prioridad, responsable y solicitante.
// Cada cambio se aplica con `applyIncidentFilters`, sin recargar la página entera.
export function IncidentFilters({ filters, assignees, requesters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const hasFilters = Boolean(
        filters.status ||
        filters.priority ||
        filters.search ||
        filters.assignee ||
        filters.requester,
    );

    function handleSearch(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        applyIncidentFilters({ ...filters, search: search.trim() || null });
    }

    function handleClear() {
        setSearch('');
        applyIncidentFilters({
            status: null,
            priority: null,
            search: null,
            assignee: null,
            requester: null,
        });
    }

    return (
        <form
            onSubmit={handleSearch}
            role="search"
            className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
        >
            <div className="grid gap-1.5 sm:col-span-2 lg:col-span-4">
                <Label htmlFor="filter-search">Buscar</Label>
                <div className="flex gap-2">
                    <Input
                        id="filter-search"
                        type="search"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Título, descripción, solicitante o responsable"
                        maxLength={100}
                    />
                    <Button type="submit" variant="secondary">
                        <Search aria-hidden="true" />
                        Buscar
                    </Button>
                </div>
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="filter-status">Estado</Label>
                <Select
                    value={filters.status ?? ALL}
                    onValueChange={(value) =>
                        applyIncidentFilters({
                            ...filters,
                            status:
                                value === ALL
                                    ? null
                                    : (value as IncidentStatus),
                        })
                    }
                >
                    <SelectTrigger id="filter-status" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todos</SelectItem>
                        {INCIDENT_STATUSES.map((status) => (
                            <SelectItem key={status} value={status}>
                                {STATUS_CONFIG[status].label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="filter-priority">Prioridad</Label>
                <Select
                    value={filters.priority ?? ALL}
                    onValueChange={(value) =>
                        applyIncidentFilters({
                            ...filters,
                            priority:
                                value === ALL
                                    ? null
                                    : (value as IncidentPriority),
                        })
                    }
                >
                    <SelectTrigger id="filter-priority" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todas</SelectItem>
                        {INCIDENT_PRIORITIES.map((priority) => (
                            <SelectItem key={priority} value={priority}>
                                {PRIORITY_CONFIG[priority].label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="filter-assignee">Responsable</Label>
                <Select
                    value={filters.assignee ?? ALL}
                    onValueChange={(value) =>
                        applyIncidentFilters({
                            ...filters,
                            assignee: value === ALL ? null : value,
                        })
                    }
                >
                    <SelectTrigger id="filter-assignee" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todos</SelectItem>
                        <SelectItem value={UNASSIGNED_FILTER}>
                            Sin asignar
                        </SelectItem>
                        {assignees.map((assignee) => (
                            <SelectItem key={assignee} value={assignee}>
                                {assignee}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="filter-requester">Solicitante</Label>
                <Select
                    value={filters.requester ?? ALL}
                    onValueChange={(value) =>
                        applyIncidentFilters({
                            ...filters,
                            requester: value === ALL ? null : value,
                        })
                    }
                >
                    <SelectTrigger id="filter-requester" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Todos</SelectItem>
                        {requesters.map((requester) => (
                            <SelectItem key={requester} value={requester}>
                                {requester}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {hasFilters && (
                <div className="sm:col-span-2 lg:col-span-4">
                    <Button type="button" variant="ghost" onClick={handleClear}>
                        <X aria-hidden="true" />
                        Limpiar filtros
                    </Button>
                </div>
            )}
        </form>
    );
}
