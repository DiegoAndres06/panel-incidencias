import { Link } from '@inertiajs/react';
import { History } from 'lucide-react';
import { StatusChangeDialog } from '@/components/incidents/status-change-dialog';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/incidents';
import type { Incident } from '@/types';

// Acciones visibles de cada incidencia: cambiar estado y abrir el historial.
export function IncidentRowActions({ incident }: { incident: Incident }) {
    return (
        <div className="flex flex-wrap items-center gap-2">
            <StatusChangeDialog incident={incident} />
            <Button variant="ghost" size="sm" asChild>
                <Link href={show(incident.id)}>
                    <History aria-hidden="true" />
                    Historial
                </Link>
            </Button>
        </div>
    );
}
