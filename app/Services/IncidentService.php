<?php

namespace App\Services;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentComment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Operaciones que modifican una incidencia y dejan su rastro en `incident_logs`.
 *
 * Cada operación se ejecuta en una transacción: o se guardan la incidencia (o el
 * comentario) y su registro de trazabilidad, o no se guarda nada.
 */
class IncidentService
{
    /**
     * Crea una incidencia abierta y registra su creación.
     *
     * El estado inicial no se puede elegir. El actor del registro es el solicitante.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Incident
    {
        return DB::transaction(function () use ($data) {
            $incident = Incident::create([
                ...Arr::only($data, ['title', 'description', 'priority', 'requester_name', 'assigned_to']),
                'status' => IncidentStatus::Open,
            ]);

            $incident->logs()->create([
                'action' => IncidentLogAction::Created,
                'new_value' => IncidentStatus::Open->value,
                'user_name' => $incident->requester_name,
            ]);

            return $incident;
        });
    }

    /**
     * Cambia el estado de una incidencia respetando las transiciones permitidas.
     *
     * Dentro de la transacción se vuelve a leer la incidencia con bloqueo de fila, así la
     * transición se valida contra el estado real de la base de datos y dos personas que
     * operen a la vez no pueden pisarse. Resolver y volver a abrir deja un registro
     * `reopened` explícito y exige un motivo.
     *
     * @throws ValidationException si la transición no es válida o falta el motivo de reapertura
     */
    public function changeStatus(Incident $incident, IncidentStatus $to, string $actor, ?string $note = null): Incident
    {
        $note = filled($note) ? trim($note) : null;

        return DB::transaction(function () use ($incident, $to, $actor, $note) {
            $current = Incident::query()->lockForUpdate()->findOrFail($incident->id);
            $from = $current->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => "No se puede pasar de «{$from->label()}» a «{$to->label()}».",
                ]);
            }

            $isReopening = $from->isReopening($to);

            if ($isReopening && $note === null) {
                throw ValidationException::withMessages([
                    'note' => 'Indica el motivo de la reapertura.',
                ]);
            }

            $current->update(['status' => $to]);

            $current->logs()->create([
                'action' => $isReopening ? IncidentLogAction::Reopened : IncidentLogAction::StatusChanged,
                'old_value' => $from->value,
                'new_value' => $to->value,
                'user_name' => $actor,
                'metadata' => $note === null ? null : ['reason' => $note],
            ]);

            return $current;
        });
    }

    /**
     * Cambia el responsable de una incidencia (o se lo quita, con `null`) y lo registra.
     *
     * Igual que `changeStatus()`, vuelve a leer la incidencia con bloqueo de fila para
     * comparar contra el valor real de la base de datos. El nombre debe ser de un
     * responsable del catálogo; eso lo valida la request, no el servicio. No toca el estado.
     *
     * @throws ValidationException si la incidencia ya tiene ese responsable (o ya no tiene ninguno)
     */
    public function reassign(Incident $incident, ?string $assignee, string $actor, ?string $note = null): Incident
    {
        $note = filled($note) ? trim($note) : null;

        return DB::transaction(function () use ($incident, $assignee, $actor, $note) {
            $current = Incident::query()->lockForUpdate()->findOrFail($incident->id);
            $from = $current->assigned_to;

            if ($from === $assignee) {
                throw ValidationException::withMessages([
                    'assigned_to' => $assignee === null
                        ? 'La incidencia ya está sin asignar.'
                        : "La incidencia ya está asignada a «{$assignee}».",
                ]);
            }

            $current->update(['assigned_to' => $assignee]);

            $current->logs()->create([
                'action' => IncidentLogAction::Assigned,
                'old_value' => $from,
                'new_value' => $assignee,
                'user_name' => $actor,
                'metadata' => $note === null ? null : ['reason' => $note],
            ]);

            return $current;
        });
    }

    /**
     * Añade un comentario operativo y deja constancia en el registro.
     */
    public function addComment(Incident $incident, string $author, string $body): IncidentComment
    {
        return DB::transaction(function () use ($incident, $author, $body) {
            $comment = $incident->comments()->create([
                'author_name' => $author,
                'body' => $body,
            ]);

            $incident->logs()->create([
                'action' => IncidentLogAction::Commented,
                'user_name' => $author,
                'metadata' => ['comment_id' => $comment->id],
            ]);

            return $comment;
        });
    }
}
