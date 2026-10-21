---
name: state-transitions
description: Máquina de estados de las incidencias, transacciones y trazabilidad. Define qué transiciones son válidas, la regla de reapertura y cómo cada cambio de estado se registra en incident_logs dentro de una transacción. Leer antes de cambiar estados o escribir en la traza.
---

# Transiciones, transacciones y trazabilidad (Ej. 2/3)

## Mapa de transiciones (fuente única: `IncidentStatus`)

```
open       → in_review
in_review  → blocked | resolved
blocked    → in_review | resolved
resolved   → in_review        (REAPERTURA, requiere log explícito)
```

- Base pedida por la prueba: `abierta → en revisión → bloqueada o resuelta`.
- `blocked → in_review` permite desbloquear antes de resolver.
- **`resolved` no vuelve a `open` directamente.** Reabrir va a `in_review` y se registra
  con `action = 'reopened'` (log explícito de reapertura).
- Cualquier transición fuera del mapa es un **error de validación** con mensaje claro en
  español (no una excepción cruda).

Implementación en el enum:

- `allowedTransitions(): array<IncidentStatus>`
- `canTransitionTo(IncidentStatus $to): bool`
- `isReopening(IncidentStatus $to): bool` (true si `resolved → in_review`)

## Servicio

Toda mutación con trazabilidad pasa por `App\Services\IncidentService`, nunca por el
controlador a pelo. Tiene cuatro operaciones, todas dentro de `DB::transaction`:

- `create()` — incidencia abierta (el estado inicial no se elige) + log `created` con el
  solicitante como actor.
- `changeStatus()` — cambio de estado (detalle abajo).
- `addComment()` — comentario + log `commented` con `metadata.comment_id`.
- `reassign()` — cambia o quita el responsable (`assigned_to`) con el mismo patrón que
  `changeStatus()`: transacción, relectura con `lockForUpdate()`, error en `assigned_to` si ya
  tiene ese responsable, y log `assigned` con `old_value`/`new_value` (nombres, `null` = sin
  asignar), actor y `metadata.reason`. No toca el estado.

### `changeStatus()`

1. Abrir **transacción** y **releer la incidencia con `lockForUpdate()`**: la transición se
   valida contra el estado real de la BD (no contra un modelo que puede estar desfasado) y
   dos personas operando a la vez no se pisan.
2. Validar la transición con el enum; si no es válida → `ValidationException` con mensaje ES
   en el campo `status` (esto incluye pasar al mismo estado).
3. Si es una reapertura y no hay motivo → `ValidationException` en el campo `note`.
4. Actualizar `incidents.status` y crear el `incident_logs` (`status_changed` o `reopened`)
   con `old_value`, `new_value`, `user_name` y `metadata.reason`.
5. Si cualquier paso falla, **ni el estado ni el log** quedan a medias.

La app no tiene autenticación: el actor (`user_name`) lo informa quien opera, con un campo
obligatorio "Tu nombre" validado en el servidor.

## Qué se registra en la traza (cambios críticos)

- Creación de incidencia → `created` (new_value = estado inicial).
- Cambio de estado → `status_changed` (old/new).
- Reapertura → `reopened` (old = resolved, new = in_review) + `metadata.reason`.
- Comentario operativo → `commented` (el comentario vive en `incident_comments`, el log
  solo deja el rastro).

## Invariantes que la implementación debe garantizar

- No existe cambio de estado sin su log asociado (misma transacción).
- No existe incidencia `resolved` sin un log de resolución (lo verifica el Ej. 5).
- El `status` actual de la incidencia es coherente con el `new_value` de su último log de
  cambio de estado (invariante que el diagnóstico del Ej. 5 comprueba).
