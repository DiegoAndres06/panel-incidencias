import { Link } from '@inertiajs/react';
import { IncidentRowActions } from '@/components/incidents/incident-row-actions';
import { PriorityBadge } from '@/components/incidents/priority-badge';
import { StatusBadge } from '@/components/incidents/status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { HIGHLIGHT_CLASS } from '@/lib/incidents/config';
import { formatDate } from '@/lib/incidents/format';
import { show } from '@/routes/incidents';
import type { Incident } from '@/types';

// Listado para pantallas medianas y grandes. En móvil se usa IncidentCards.
export function IncidentTable({ incidents }: { incidents: Incident[] }) {
    return (
        <div className="hidden rounded-xl border md:block">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Incidencia</TableHead>
                        <TableHead>Estado</TableHead>
                        <TableHead>Prioridad</TableHead>
                        <TableHead>Solicitante / Responsable</TableHead>
                        <TableHead>Creada</TableHead>
                        <TableHead>Acciones</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {incidents.map((incident) => (
                        <TableRow
                            key={incident.id}
                            className={HIGHLIGHT_CLASS[incident.status]?.row}
                        >
                            <TableCell className="max-w-xs">
                                <Link
                                    href={show(incident.id)}
                                    className="font-medium hover:underline"
                                >
                                    <span className="text-muted-foreground">
                                        #{incident.id}
                                    </span>{' '}
                                    {incident.title}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <StatusBadge status={incident.status} />
                            </TableCell>
                            <TableCell>
                                <PriorityBadge priority={incident.priority} />
                            </TableCell>
                            <TableCell>
                                <div>{incident.requester_name}</div>
                                <div className="text-xs text-muted-foreground">
                                    {incident.assigned_to ?? 'Sin asignar'}
                                </div>
                            </TableCell>
                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                {formatDate(incident.created_at)}
                            </TableCell>
                            <TableCell>
                                <IncidentRowActions incident={incident} />
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
