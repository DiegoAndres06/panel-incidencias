import { Form } from '@inertiajs/react';
import { UserRoundCog } from 'lucide-react';
import { useState } from 'react';
import IncidentAssigneeController from '@/actions/App/Http/Controllers/IncidentAssigneeController';
import { ActorNameField } from '@/components/incidents/actor-name-field';
import { AssigneeSelect } from '@/components/incidents/assignee-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import type { Incident } from '@/types';

type Props = {
    incident: Pick<Incident, 'id' | 'title' | 'assigned_to'>;
    assignees: string[];
};

// Diálogo para cambiar el responsable de una incidencia o quitárselo. El cambio queda en el
// historial con quien lo hizo y, si se indica, el motivo. Si el servidor lo rechaza (por
// ejemplo, porque ya tiene ese responsable), el error se muestra en el propio formulario.
export function AssigneeDialog({ incident, assignees }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <UserRoundCog aria-hidden="true" />
                    Reasignar
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto">
                <Form
                    {...IncidentAssigneeController.update.form(incident.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Cambiar responsable</DialogTitle>
                                <DialogDescription>
                                    #{incident.id} · {incident.title}
                                </DialogDescription>
                            </DialogHeader>

                            <p className="text-sm">
                                <span className="text-muted-foreground">
                                    Responsable actual:{' '}
                                </span>
                                {incident.assigned_to ?? 'Sin asignar'}
                            </p>

                            <div className="grid gap-2">
                                <Label htmlFor={`assignee-${incident.id}`}>
                                    Nuevo responsable
                                </Label>
                                <AssigneeSelect
                                    id={`assignee-${incident.id}`}
                                    assignees={assignees}
                                    defaultValue={incident.assigned_to}
                                    invalid={Boolean(errors.assigned_to)}
                                />
                                <InputError message={errors.assigned_to} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`assignee-note-${incident.id}`}>
                                    Motivo (opcional)
                                </Label>
                                <Textarea
                                    id={`assignee-note-${incident.id}`}
                                    name="note"
                                    placeholder="Contexto del cambio para el historial"
                                    aria-invalid={Boolean(errors.note)}
                                />
                                <InputError message={errors.note} />
                            </div>

                            <ActorNameField
                                id={`assignee-user-name-${incident.id}`}
                                name="user_name"
                                error={errors.user_name}
                            />

                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Guardar responsable
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
