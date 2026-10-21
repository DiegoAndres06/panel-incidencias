import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { applyIncidentFilters } from '@/lib/incidents/visit';
import type { IncidentListFilters, IncidentPagination } from '@/types';

// Resumen de lo que se está viendo y botones para cambiar de página. Los filtros se
// conservan al cambiar de página.
export function IncidentPaginationBar({
    pagination,
    filters,
}: {
    pagination: IncidentPagination;
    filters: IncidentListFilters;
}) {
    const { current_page, last_page, from, to, total } = pagination;

    if (total === 0) {
        return null;
    }

    return (
        <nav
            aria-label="Paginación del listado"
            className="flex flex-col items-center justify-between gap-3 sm:flex-row"
        >
            <p className="text-sm text-muted-foreground">
                Mostrando {from}–{to} de {total}{' '}
                {total === 1 ? 'incidencia' : 'incidencias'}
            </p>

            {last_page > 1 && (
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={current_page <= 1}
                        onClick={() =>
                            applyIncidentFilters(filters, current_page - 1)
                        }
                    >
                        <ChevronLeft aria-hidden="true" />
                        Anterior
                    </Button>
                    <span className="text-sm tabular-nums">
                        Página {current_page} de {last_page}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={current_page >= last_page}
                        onClick={() =>
                            applyIncidentFilters(filters, current_page + 1)
                        }
                    >
                        Siguiente
                        <ChevronRight aria-hidden="true" />
                    </Button>
                </div>
            )}
        </nav>
    );
}
