import { router } from '@inertiajs/react';
import { index } from '@/routes/incidents';
import type { IncidentListFilters } from '@/types';

// Aplica unos filtros (y, si se indica, una página) al listado. Viajan en la URL, así que son
// enlazables y sobreviven a recargar. Pide solo lo que cambia y conserva el estado y el scroll
// de la página, sin recargarla entera. Cambiar los filtros vuelve a la primera página.
export function applyIncidentFilters(
    next: IncidentListFilters,
    page?: number,
): void {
    const query: Record<string, string | number> = {};

    for (const [key, value] of Object.entries(next)) {
        if (value) {
            query[key] = value;
        }
    }

    if (page !== undefined && page > 1) {
        query.page = page;
    }

    router.get(index.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['incidents', 'filters', 'counters', 'pagination'],
    });
}
