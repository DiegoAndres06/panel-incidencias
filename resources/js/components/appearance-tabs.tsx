import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

// Selector de tema (claro, oscuro o el del sistema). Solo muestra iconos para caber en el
// sidebar; cada botón lleva su texto para lectores de pantalla.
export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Tema claro' },
        { value: 'dark', icon: Moon, label: 'Tema oscuro' },
        { value: 'system', icon: Monitor, label: 'Tema del sistema' },
    ];

    return (
        <div
            role="group"
            aria-label="Tema de la aplicación"
            className={cn(
                'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => updateAppearance(value)}
                    aria-pressed={appearance === value}
                    title={label}
                    className={cn(
                        'flex flex-1 items-center justify-center rounded-md px-3 py-1.5 transition-colors',
                        appearance === value
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    )}
                >
                    <Icon className="size-4" aria-hidden="true" />
                    <span className="sr-only">{label}</span>
                </button>
            ))}
        </div>
    );
}
