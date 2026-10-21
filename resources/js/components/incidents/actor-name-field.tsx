import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { readActorName, rememberActorName } from '@/lib/actor-name';

type Props = {
    id: string;
    // Nombre del campo en la petición: el servidor lo espera distinto en cada formulario.
    name: 'user_name' | 'author_name';
    error?: string;
};

// Campo «Tu nombre»: quien hace un cambio lo escribe y queda en el historial. Aparece ya
// relleno con el último nombre usado en este navegador (ver `lib/actor-name.ts`) y guarda
// el que se escriba cuando su formulario se envía.
export function ActorNameField({ id, name, error }: Props) {
    const inputRef = useRef<HTMLInputElement>(null);

    // Se lee al montarse. Los diálogos montan su contenido al abrirse, así que siempre
    // muestran el último nombre guardado, también si se guardó desde otro formulario.
    const [initialName] = useState(readActorName);

    // Se guarda al enviar el formulario (con clic o con Intro), no en cada tecla: así no
    // queda un nombre a medias si se cancela. El evento `submit` solo salta cuando el
    // navegador ya ha validado los campos obligatorios.
    useEffect(() => {
        const form = inputRef.current?.form;

        if (!form) {
            return;
        }

        const remember = () => rememberActorName(inputRef.current?.value);

        form.addEventListener('submit', remember);

        return () => form.removeEventListener('submit', remember);
    }, []);

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>Tu nombre</Label>
            <Input
                ref={inputRef}
                id={id}
                name={name}
                defaultValue={initialName}
                maxLength={120}
                required
                aria-invalid={Boolean(error)}
            />
            <InputError message={error} />
        </div>
    );
}
