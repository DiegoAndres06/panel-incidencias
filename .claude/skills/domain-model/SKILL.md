---
name: domain-model
description: Modelo de datos del Panel de Incidencias — migraciones MySQL, enums de estado/prioridad, claves foráneas, índices y las tablas incidents / incident_logs / incident_comments. Leer antes de crear o modificar migraciones, modelos o enums.
---

# Modelo de datos (Ejercicio 2)

## Enums (fuente única de verdad, sin strings mágicos)

`app/Enums/IncidentStatus.php` — enum string respaldado:

- `open` → "Abierta"
- `in_review` → "En revisión"
- `blocked` → "Bloqueada"
- `resolved` → "Resuelta"

`app/Enums/IncidentPriority.php` — enum string respaldado:

- `low` → "Baja", `medium` → "Media", `high` → "Alta", `critical` → "Crítica"

`app/Enums/IncidentLogAction.php` — tipos de acción de la traza: `created`, `status_changed`,
`reopened`, `commented` y `assigned` (cambio de responsable; `old_value`/`new_value` son nombres,
no estados, y `affectsStatus()` es falso). Casteado en
`IncidentLog.action`; nunca se compara contra un string suelto.

`IncidentStatus` e `IncidentPriority` exponen:

- `label(): string` — texto en español para la UI.
- `IncidentStatus`: métodos de transición (ver skill `state-transitions`).
- un método estático `options()` que devuelve `[value => label]` para selects y para
  serializar al frontend.

## Tablas

### `incidents`

| Columna                   | Tipo                 | Notas                                                         |
| ------------------------- | -------------------- | ------------------------------------------------------------- |
| `id`                      | bigIncrements        | PK                                                            |
| `title`                   | string(160)          | requerido                                                     |
| `description`             | text nullable        |                                                               |
| `status`                  | string(20)           | default `open`, casteado a `IncidentStatus`                   |
| `priority`                | string(20)           | default `medium`, casteado a `IncidentPriority`               |
| `requester_name`          | string(120)          | solicitante                                                   |
| `assigned_to`             | string(120) nullable | responsable: nombre elegido del catálogo `assignees` (sin FK) |
| `created_at`/`updated_at` | timestamps           |                                                               |

Índices (sin redundancias): `(status, created_at)`, `(priority, created_at)`,
`(assigned_to, created_at)`, `(requester_name, created_at)` y `created_at`. Cada filtro del listado usa su índice con la fecha como segunda columna,
y el listado sin filtros usa el de `created_at`. Un índice solo por `status` sería
redundante con el compuesto. `status` y `priority` son `string`, no `ENUM` de MySQL: los
valores válidos los define el enum de PHP.

### `assignees` (catálogo de responsables)

| Columna                   | Tipo          | Notas                                |
| ------------------------- | ------------- | ------------------------------------ |
| `id`                      | bigIncrements | PK                                   |
| `name`                    | string(120)   | **único**; lo llena `AssigneeSeeder` |
| `created_at`/`updated_at` | timestamps    |                                      |

Sin clave foránea desde `incidents`: `incidents.assigned_to` guarda el **nombre**, que se elige
del catálogo y se valida con `Rule::exists('assignees', 'name')`, igual que `incident_logs`
guarda los nombres que había en cada momento. Se evitó la FK para no convertir una columna con
datos reales. Contra: renombrar a alguien no actualiza las incidencias antiguas. Los
solicitantes (`requester_name`) siguen siendo texto libre.

### `incident_logs` (trazabilidad, solo append)

| Columna       | Tipo                 | Notas                                                      |
| ------------- | -------------------- | ---------------------------------------------------------- |
| `id`          | bigIncrements        | PK                                                         |
| `incident_id` | foreignId            | FK → `incidents`, `cascadeOnDelete`                        |
| `action`      | string(40)           | p.ej. `created`, `status_changed`, `reopened`, `commented` |
| `old_value`   | string(40) nullable  | estado anterior cuando aplica                              |
| `new_value`   | string(40) nullable  | estado nuevo cuando aplica                                 |
| `user_name`   | string(120) nullable | actor del cambio                                           |
| `metadata`    | json nullable        | contexto extra (motivo, ip, etc.)                          |
| `created_at`  | timestamp useCurrent | **sin** `updated_at` (`const UPDATED_AT = null`)           |

Índices: `(incident_id, created_at)` (historial de una incidencia; sirve también de índice
de la FK, MySQL descarta el que crea solo) y `action`.

### `incident_comments`

| Columna                   | Tipo          | Notas                               |
| ------------------------- | ------------- | ----------------------------------- |
| `id`                      | bigIncrements | PK                                  |
| `incident_id`             | foreignId     | FK → `incidents`, `cascadeOnDelete` |
| `author_name`             | string(120)   | autor                               |
| `body`                    | text          | requerido                           |
| `created_at`/`updated_at` | timestamps    |                                     |

Índices: `(incident_id, created_at)` (también cubre la FK).

## Modelos Eloquent

- `Incident` hasMany `logs` y `comments`, **sin orden fijo en la relación**: el orden se
  pide donde se usa (`->latest()`), para no interferir con `whereDoesntHave` ni con las
  consultas del diagnóstico (Ej. 5).
- `IncidentLog` belongsTo `Incident`; casts `action` → `IncidentLogAction` y `metadata` →
  `array`; `UPDATED_AT = null`.
- `IncidentComment` belongsTo `Incident`.
- `Incident` castea `status` → `IncidentStatus` y `priority` → `IncidentPriority`.
- Atributos `#[Fillable([...])]` explícitos (estilo de Laravel 13 y de `User`), **nunca**
  `$guarded = []`. En `IncidentLog` e `IncidentComment` `incident_id` no es fillable: se
  asigna creando a través de la relación (`$incident->logs()->create(...)`).
- Factories en `database/factories`; `IncidentFactory::withStatus()` fija el estado.

## Reglas

- FKs siempre con `cascadeOnDelete` (borrar incidencia limpia su traza y comentarios).
- No escribir estado como literal: usar siempre el enum.
- Las escrituras de `incident_logs` NO se hacen sueltas: ver skill `state-transitions`.
