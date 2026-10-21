import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import IncidentController from '@/actions/App/Http/Controllers/IncidentController';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { INCIDENT_PRIORITIES, PRIORITY_CONFIG } from '@/lib/incidents/config';

// Formulario de alta de incidencia. Las validaciones las hace el servidor y sus
// mensajes (en español) se muestran bajo cada campo. El diálogo se cierra solo si
// la incidencia se guarda; al cerrarse se descarta lo escrito.
export function IncidentFormDialog({ assignees }: { assignees: string[] }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>
                    <Plus aria-hidden="true" />
                    Nueva incidencia
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto">
                <Form
                    {...IncidentController.store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Nueva incidencia</DialogTitle>
                                <DialogDescription>
                                    Describe el problema para que el equipo
                                    pueda atenderlo.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Título</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    maxLength={160}
                                    required
                                    aria-invalid={Boolean(errors.title)}
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Descripción</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    aria-invalid={Boolean(errors.description)}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="priority">Prioridad</Label>
                                    <Select
                                        name="priority"
                                        defaultValue="medium"
                                    >
                                        <SelectTrigger
                                            id="priority"
                                            className="w-full"
                                            aria-invalid={Boolean(
                                                errors.priority,
                                            )}
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {INCIDENT_PRIORITIES.map(
                                                (priority) => (
                                                    <SelectItem
                                                        key={priority}
                                                        value={priority}
                                                    >
                                                        {
                                                            PRIORITY_CONFIG[
                                                                priority
                                                            ].label
                                                        }
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.priority} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="requester_name">
                                        Solicitante
                                    </Label>
                                    <Input
                                        id="requester_name"
                                        name="requester_name"
                                        maxLength={120}
                                        required
                                        aria-invalid={Boolean(
                                            errors.requester_name,
                                        )}
                                    />
                                    <InputError
                                        message={errors.requester_name}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="assigned_to">
                                    Responsable (opcional)
                                </Label>
                                <AssigneeSelect
                                    id="assigned_to"
                                    assignees={assignees}
                                    invalid={Boolean(errors.assigned_to)}
                                />
                                <InputError message={errors.assigned_to} />
                            </div>

                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Crear incidencia
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
