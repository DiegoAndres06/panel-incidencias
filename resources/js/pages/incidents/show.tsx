import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { AssigneeDialog } from '@/components/incidents/assignee-dialog';
import { CommentForm } from '@/components/incidents/comment-form';
import { CommentList } from '@/components/incidents/comment-list';
import { HistoryTimeline } from '@/components/incidents/history-timeline';
import { PriorityBadge } from '@/components/incidents/priority-badge';
import { StatusBadge } from '@/components/incidents/status-badge';
import { StatusChangeDialog } from '@/components/incidents/status-change-dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/incidents/format';
import { index } from '@/routes/incidents';
import type { Incident, IncidentComment, IncidentLog } from '@/types';

type Props = {
    incident: Incident;
    logs: IncidentLog[];
    comments: IncidentComment[];
    // Catálogo de responsables, para el diálogo de reasignación.
    assignees: string[];
};

export default function IncidentsShow({
    incident,
    logs,
    comments,
    assignees,
}: Props) {
    return (
        <>
            <Head title={`Incidencia #${incident.id}`} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <Link
                    href={index()}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft className="size-4" aria-hidden="true" />
                    Volver al listado
                </Link>

                <header className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            <span className="text-muted-foreground">
                                #{incident.id}
                            </span>{' '}
                            {incident.title}
                        </h1>
                        <div className="flex flex-wrap gap-2">
                            <StatusBadge status={incident.status} />
                            <PriorityBadge priority={incident.priority} />
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <AssigneeDialog
                            incident={incident}
                            assignees={assignees}
                        />
                        <StatusChangeDialog incident={incident} />
                    </div>
                </header>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalle</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 text-sm">
                            <p className="whitespace-pre-wrap">
                                {incident.description ??
                                    'Sin descripción registrada.'}
                            </p>
                            <dl className="grid grid-cols-2 gap-4">
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
                                    <dd>
                                        {incident.assigned_to ?? 'Sin asignar'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Creada
                                    </dt>
                                    <dd>{formatDate(incident.created_at)}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-muted-foreground">
                                        Actualizada
                                    </dt>
                                    <dd>{formatDate(incident.updated_at)}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Historial de cambios</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <HistoryTimeline logs={logs} />
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Comentarios</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6 lg:grid-cols-2">
                        <CommentForm incidentId={incident.id} />
                        <CommentList comments={comments} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

IncidentsShow.layout = {
    breadcrumbs: [{ title: 'Incidencias', href: index() }],
};
