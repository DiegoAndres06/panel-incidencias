import {
    ArrowDown,
    ArrowUp,
    ChevronsUp,
    CircleCheck,
    CircleDot,
    Eye,
    Minus,
    OctagonX,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type {
    IncidentLogAction,
    IncidentPriority,
    IncidentStatus,
} from '@/types';

type BadgeConfig = {
    label: string;
    icon: LucideIcon;
    badgeClass: string;
};

// Orden de presentación de los estados (flujo normal de una incidencia).
export const INCIDENT_STATUSES: IncidentStatus[] = [
    'open',
    'in_review',
    'blocked',
    'resolved',
];

export const INCIDENT_PRIORITIES: IncidentPriority[] = [
    'low',
    'medium',
    'high',
    'critical',
];

// Único lugar con textos y colores de estado. Cada estado se distingue por
// color, icono y texto, para que no dependa solo del color.
export const STATUS_CONFIG: Record<IncidentStatus, BadgeConfig> = {
    open: {
        label: 'Abierta',
        icon: CircleDot,
        badgeClass:
            'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300',
    },
    in_review: {
        label: 'En revisión',
        icon: Eye,
        badgeClass:
            'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
    },
    blocked: {
        label: 'Bloqueada',
        icon: OctagonX,
        badgeClass:
            'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300',
    },
    resolved: {
        label: 'Resuelta',
        icon: CircleCheck,
        badgeClass:
            'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300',
    },
};

export const PRIORITY_CONFIG: Record<IncidentPriority, BadgeConfig> = {
    low: {
        label: 'Baja',
        icon: ArrowDown,
        badgeClass:
            'border-zinc-200 bg-transparent text-zinc-600 dark:border-zinc-800 dark:text-zinc-400',
    },
    medium: {
        label: 'Media',
        icon: Minus,
        badgeClass:
            'border-zinc-300 bg-zinc-100 text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-zinc-200',
    },
    high: {
        label: 'Alta',
        icon: ArrowUp,
        badgeClass:
            'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-900 dark:bg-orange-950/40 dark:text-orange-300',
    },
    critical: {
        label: 'Crítica',
        icon: ChevronsUp,
        badgeClass: 'border-transparent bg-red-600 text-white dark:bg-red-700',
    },
};

// Textos de la confirmación que se pide antes de cambiar a estos estados. Los demás
// cambios se envían directamente.
const CONFIRMATIONS: Partial<
    Record<
        IncidentStatus,
        { title: string; description: string; action: string }
    >
> = {
    resolved: {
        title: 'Vas a resolver la incidencia',
        description:
            'Se marcará como resuelta. Podrá reabrirse más adelante, pero para ello habrá que indicar un motivo.',
        action: 'Sí, resolver',
    },
    blocked: {
        title: 'Vas a bloquear la incidencia',
        description:
            'Se marcará como bloqueada. Conviene indicar el motivo para que el equipo sepa qué la detiene.',
        action: 'Sí, bloquear',
    },
};

// Devuelve la confirmación que hay que pedir para pasar a `status`, o `undefined` si
// ese cambio no la necesita.
export function confirmationFor(status: string) {
    return CONFIRMATIONS[status as IncidentStatus];
}

// Clases extra para destacar las incidencias que piden atención (hoy, las bloqueadas):
// una para la fila de la tabla y otra para la tarjeta móvil. Se suman al badge de estado,
// que ya distingue el estado por icono y texto además de por color.
export const HIGHLIGHT_CLASS: Partial<
    Record<IncidentStatus, { row: string; card: string }>
> = {
    blocked: {
        row: 'bg-red-50/60 shadow-[inset_3px_0_0_var(--color-red-500)] hover:bg-red-50 dark:bg-red-950/20 dark:hover:bg-red-950/30',
        card: 'border-red-300 bg-red-50/60 dark:border-red-900 dark:bg-red-950/20',
    },
};

// Valor del filtro de responsable que muestra solo las incidencias sin responsable.
export const UNASSIGNED_FILTER = 'unassigned';

export const LOG_ACTION_LABELS: Record<IncidentLogAction, string> = {
    created: 'Incidencia creada',
    status_changed: 'Cambio de estado',
    reopened: 'Reapertura',
    commented: 'Comentario',
    assigned: 'Cambio de responsable',
};

// Convierte el valor de texto de un log en un estado conocido, o null si no lo es.
export function toStatus(value: string | null): IncidentStatus | null {
    return value !== null && value in STATUS_CONFIG
        ? (value as IncidentStatus)
        : null;
}
