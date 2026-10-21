import { ArrowRight } from 'lucide-react';
import { StatusBadge } from '@/components/incidents/status-badge';
import { LOG_ACTION_LABELS, toStatus } from '@/lib/incidents/config';
import { formatDate } from '@/lib/incidents/format';
import type { IncidentLog } from '@/types';

// Línea de tiempo vertical con los cambios registrados de una incidencia.
export function HistoryTimeline({ logs }: { logs: IncidentLog[] }) {
    if (logs.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Esta incidencia todavía no tiene cambios registrados.
            </p>
        );
    }

    return (
        <ol className="relative ms-2 space-y-6 border-s ps-6">
            {logs.map((log) => {
                const oldStatus = toStatus(log.old_value);
                const newStatus = toStatus(log.new_value);
                const reason = log.metadata?.reason;

                return (
                    <li key={log.id} className="relative">
                        <span
                            aria-hidden="true"
                            className="absolute top-1.5 -left-[1.9rem] size-2.5 rounded-full border-2 border-background bg-foreground"
                        />
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="font-medium">
                                {LOG_ACTION_LABELS[log.action]}
                            </span>
                            {log.action === 'assigned' && (
                                <span className="flex flex-wrap items-center gap-1.5 text-sm">
                                    <span>
                                        {log.old_value ?? 'Sin asignar'}
                                    </span>
                                    <ArrowRight
                                        aria-hidden="true"
                                        className="size-3.5 text-muted-foreground"
                                    />
                                    <span className="font-medium">
                                        {log.new_value ?? 'Sin asignar'}
                                    </span>
                                </span>
                            )}
                            {log.action !== 'assigned' && newStatus && (
                                <span className="flex flex-wrap items-center gap-1.5">
                                    {oldStatus && (
                                        <>
                                            <StatusBadge status={oldStatus} />
                                            <ArrowRight
                                                aria-hidden="true"
                                                className="size-3.5 text-muted-foreground"
                                            />
                                        </>
                                    )}
                                    <StatusBadge status={newStatus} />
                                </span>
                            )}
                        </div>
                        {typeof reason === 'string' && (
                            <p className="mt-1 text-sm">{reason}</p>
                        )}
                        <p className="mt-1 text-xs text-muted-foreground">
                            {log.user_name ?? 'Sistema'} ·{' '}
                            {formatDate(log.created_at)}
                        </p>
                    </li>
                );
            })}
        </ol>
    );
}
