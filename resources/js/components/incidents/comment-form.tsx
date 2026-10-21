import { Form } from '@inertiajs/react';
import IncidentCommentController from '@/actions/App/Http/Controllers/IncidentCommentController';
import { ActorNameField } from '@/components/incidents/actor-name-field';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

// Formulario para añadir un comentario operativo. Al guardarse solo se vacía el texto
// del comentario; el nombre del autor se conserva para el siguiente.
export function CommentForm({ incidentId }: { incidentId: number }) {
    return (
        <Form
            {...IncidentCommentController.store.form(incidentId)}
            options={{ preserveScroll: true }}
            resetOnSuccess={['body']}
            className="grid gap-4"
        >
            {({ processing, errors }) => (
                <>
                    <ActorNameField
                        id="author_name"
                        name="author_name"
                        error={errors.author_name}
                    />

                    <div className="grid gap-2">
                        <Label htmlFor="body">Comentario</Label>
                        <Textarea
                            id="body"
                            name="body"
                            placeholder="Novedades o contexto de seguimiento"
                            required
                            aria-invalid={Boolean(errors.body)}
                        />
                        <InputError message={errors.body} />
                    </div>

                    <div>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Añadir comentario
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
