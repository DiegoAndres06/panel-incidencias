import { useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

// Radix no admite una opción con valor vacío, así que "Sin asignar" usa un valor propio y
// el campo que viaja con el formulario (`<input type="hidden">`) lo convierte en vacío.
const NONE = 'none';

type Props = {
    id: string;
    assignees: string[];
    // Responsable actual, si lo hay.
    defaultValue?: string | null;
    invalid?: boolean;
    name?: string;
};

// Selector de responsable: se elige de la lista del catálogo, o "Sin asignar".
export function AssigneeSelect({
    id,
    assignees,
    defaultValue = null,
    invalid = false,
    name = 'assigned_to',
}: Props) {
    const [value, setValue] = useState(defaultValue ?? NONE);

    // Una incidencia antigua puede tener un responsable que ya no está en el catálogo:
    // se añade a las opciones para que el selector pueda mostrarlo.
    const options =
        defaultValue && !assignees.includes(defaultValue)
            ? [defaultValue, ...assignees]
            : assignees;

    return (
        <>
            <input
                type="hidden"
                name={name}
                value={value === NONE ? '' : value}
            />
            <Select value={value} onValueChange={setValue}>
                <SelectTrigger
                    id={id}
                    className="w-full"
                    aria-invalid={invalid}
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={NONE}>Sin asignar</SelectItem>
                    {options.map((assignee) => (
                        <SelectItem key={assignee} value={assignee}>
                            {assignee}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    );
}
