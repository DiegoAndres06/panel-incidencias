import { Form } from '@inertiajs/react';
import { RefreshCw, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import IncidentStatusController from '@/actions/App/Http/Controllers/IncidentStatusController';
import { ActorNameField } from '@/components/incidents/actor-name-field';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/incidents/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { STATUS_CONFIG, confirmationFor } from '@/lib/incidents/config';
import type { Incident } from '@/types';

type Props = {
    incident: Pick<Incident, 'id' | 'title' | 'status' | 'allowed_transitions'>;
};

// Diálogo para cambiar el estado de una incidencia. Lo usan el listado y el detalle.
// Los estados que ofrece los decide el servidor (`allowed_transitions`); si aun así
// el servidor rechaza el cambio, el error se muestra en el propio formulario.
//
// Resolver o bloquear exige una confirmación explícita antes de enviar: el primer paso
// valida el formulario y enseña un aviso, y solo el segundo lo envía. Los campos siguen
// montados durante la confirmación para que viajen con el envío.
export function StatusChangeDialog({ incident }: Props) {
    const [open, setOpen] = useState(false);
    const [nextStatus, setNextStatus] = useState('');
    const [confirming, setConfirming] = useState(false);

    // Una incidencia resuelta solo puede reabrirse, y eso exige indicar el motivo.
    const isReopening = incident.status === 'resolved';
    const confirmation = confirmationFor(nextStatus);

    function handleOpenChange(isOpen: boolean) {
        setOpen(isOpen);

        if (!isOpen) {
            setNextStatus('');
            setConfirming(false);
        }
    }

    function handleStatusChange(status: string) {
        setNextStatus(status);
        setConfirming(false);
    }

    // Pulsar Intro en un campo enviaría el formulario sin pasar por la confirmación. Se
    // intercepta en la fase de captura, antes del `onSubmit` interno de `<Form>`.
    function handleSubmitCapture(event: FormEvent<HTMLFormElement>) {
        if (confirmation && !confirming) {
            event.preventDefault();
            event.stopPropagation();
            setConfirming(true);
        }
    }

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <RefreshCw aria-hidden="true" />
                    {isReopening ? 'Reabrir' : 'Cambiar estado'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[calc(100dvh-2rem)] overflow-y-auto">
                <Form
                    {...IncidentStatusController.update.form(incident.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => handleOpenChange(false)}
                    onSubmitCapture={handleSubmitCapture}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    {isReopening
                                        ? 'Reabrir incidencia'
                                        : 'Cambiar estado'}
                                </DialogTitle>
                                <DialogDescription>
                                    #{incident.id} · {incident.title}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="flex items-center gap-2 text-sm">
                                <span className="text-muted-foreground">
                                    Actual:
                                </span>
                                <StatusBadge status={incident.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`status-${incident.id}`}>
                                    Nuevo estado
                                </Label>
                                <Select
                                    name="status"
                                    value={nextStatus}
                                    onValueChange={handleStatusChange}
                                    required
                                >
                                    <SelectTrigger
                                        id={`status-${incident.id}`}
                                        className="w-full"
                                        aria-invalid={Boolean(errors.status)}
                                    >
                                        <SelectValue placeholder="Selecciona un estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {incident.allowed_transitions.map(
                                            (status) => (
                                                <SelectItem
                                                    key={status}
                                                    value={status}
                                                >
                                                    {
                                                        STATUS_CONFIG[status]
                                                            .label
                                                    }
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor={`note-${incident.id}`}>
                                    {isReopening
                                        ? 'Motivo de la reapertura'
                                        : 'Motivo (opcional)'}
                                </Label>
                                <Textarea
                                    id={`note-${incident.id}`}
                                    name="note"
                                    placeholder="Contexto del cambio para el historial"
                                    required={isReopening}
                                    aria-invalid={Boolean(errors.note)}
                                />
                                <InputError message={errors.note} />
                            </div>

                            <ActorNameField
                                id={`user-name-${incident.id}`}
                                name="user_name"
                                error={errors.user_name}
                            />

                            {confirmation && confirming && (
                                <Alert>
                                    <TriangleAlert aria-hidden="true" />
                                    <AlertTitle>
                                        {confirmation.title}
                                    </AlertTitle>
                                    <AlertDescription>
                                        {confirmation.description}
                                    </AlertDescription>
                                </Alert>
                            )}

                            <DialogFooter>
                                {confirmation && confirming ? (
                                    <>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={processing}
                                            onClick={() => setConfirming(false)}
                                        >
                                            Volver
                                        </Button>
                                        <Button
                                            type="submit"
                                            variant={
                                                nextStatus === 'blocked'
                                                    ? 'destructive'
                                                    : 'default'
                                            }
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {confirmation.action}
                                        </Button>
                                    </>
                                ) : confirmation ? (
                                    <Button
                                        type="button"
                                        onClick={(event) => {
                                            // Valida los campos obligatorios antes de pedir la
                                            // confirmación, para no confirmar algo que no se puede enviar.
                                            if (
                                                event.currentTarget.form?.reportValidity()
                                            ) {
                                                setConfirming(true);
                                            }
                                        }}
                                    >
                                        Continuar
                                    </Button>
                                ) : (
                                    <Button
                                        type="submit"
                                        disabled={
                                            processing || nextStatus === ''
                                        }
                                    >
                                        {processing && <Spinner />}
                                        Confirmar cambio
                                    </Button>
                                )}
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
