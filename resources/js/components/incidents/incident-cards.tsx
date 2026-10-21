import { Link } from '@inertiajs/react';
import { IncidentRowActions } from '@/components/incidents/incident-row-actions';
import { PriorityBadge } from '@/components/incidents/priority-badge';
import { StatusBadge } from '@/components/incidents/status-badge';
import { HIGHLIGHT_CLASS } from '@/lib/incidents/config';
import { formatDate } from '@/lib/incidents/format';
import { cn } from '@/lib/utils';
import { show } from '@/routes/incidents';
import type { Incident } from '@/types';

// Listado para móvil. En pantallas medianas y grandes se usa IncidentTable.
export function IncidentCards({ incidents }: { incidents: Incident[] }) {
    return (
        <ul className="grid gap-3 md:hidden">
            {incidents.map((incident) => (
                <li
                    key={incident.id}
                    className={cn(
                        'grid gap-3 rounded-xl border p-4',
                        HIGHLIGHT_CLASS[incident.status]?.card,
                    )}
                >
                    <Link
                        href={show(incident.id)}
                        className="font-medium hover:underline"
                    >
                        <span className="text-muted-foreground">
                            #{incident.id}
                        </span>{' '}
                        {incident.title}
                    </Link>

                    <div className="flex flex-wrap gap-2">
                        <StatusBadge status={incident.status} />
                        <PriorityBadge priority={incident.priority} />
                    </div>

                    <dl className="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Solicitante
                            </dt>
                            <dd>{incident.requester_name}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-muted-foreground">
                                Responsable
                            </dt>
                            <dd>{incident.assigned_to ?? 'Sin asignar'}</dd>
                        </div>
                        <div className="col-span-2">
                            <dt className="text-xs text-muted-foreground">
                                Creada
                            </dt>
                            <dd>{formatDate(incident.created_at)}</dd>
                        </div>
                    </dl>

                    <IncidentRowActions incident={incident} />
                </li>
            ))}
        </ul>
    );
}
