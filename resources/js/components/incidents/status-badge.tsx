import { Badge } from '@/components/ui/badge';
import { STATUS_CONFIG } from '@/lib/incidents/config';
import { cn } from '@/lib/utils';
import type { IncidentStatus } from '@/types';

export function StatusBadge({
    status,
    className,
}: {
    status: IncidentStatus;
    className?: string;
}) {
    const { label, icon: Icon, badgeClass } = STATUS_CONFIG[status];

    return (
        <Badge variant="outline" className={cn(badgeClass, className)}>
            <Icon aria-hidden="true" />
            {label}
        </Badge>
    );
}
