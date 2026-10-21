import { Badge } from '@/components/ui/badge';
import { PRIORITY_CONFIG } from '@/lib/incidents/config';
import { cn } from '@/lib/utils';
import type { IncidentPriority } from '@/types';

export function PriorityBadge({
    priority,
    className,
}: {
    priority: IncidentPriority;
    className?: string;
}) {
    const { label, icon: Icon, badgeClass } = PRIORITY_CONFIG[priority];

    return (
        <Badge variant="outline" className={cn(badgeClass, className)}>
            <Icon aria-hidden="true" />
            {label}
        </Badge>
    );
}
