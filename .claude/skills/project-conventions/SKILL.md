---
name: project-conventions
description: Convenciones base del proyecto Panel Operativo de Incidencias (stack, idioma de identificadores vs UI, casing, glosario del dominio y estructura de carpetas). Leer antes de nombrar cualquier símbolo, archivo o ruta.
---

# Convenciones del proyecto

## Stack (obligatorio por la prueba)

- **PHP 8.4 + Laravel 13**, **MySQL 8** como base de datos.
- Frontend **Inertia 3 + React 19 + TypeScript**, estilos con **Tailwind v4**, build con **Vite**.
- Nada de Next.js. La interacción vive en componentes React servidos por Inertia.

## Idioma — regla base

Todo **identificador** (lo que lee la máquina) va en **inglés**; toda **prosa**
(comentarios, PHPDoc/JSDoc) y todo **texto de usuario final** va en **español**.

- **Inglés:** clases, métodos, variables, props, enums y sus casos, tablas, columnas,
  índices, FKs, nombres de ruta (`->name('...')`), claves de array de estado.
- **Español:** comentarios, mensajes de validación, labels de UI, notificaciones,
  textos de error que ve el usuario, mensajes de commit.
- En un array estado→texto, la **clave** va en inglés y el **valor** en español:
  `['open' => 'Abierta', 'resolved' => 'Resuelta']`.

## Casing

- **PHP:** clases/enums `PascalCase`, métodos/propiedades/variables `camelCase`,
  constantes `UPPER_SNAKE_CASE`, tablas/columnas `snake_case`.
- **React/TS:** componentes `PascalCase`, hooks `useCamelCase`, variables/funciones
  `camelCase`, props `camelCase`.

## Glosario del dominio (ES → EN) — usar SIEMPRE

| Español                                      | Inglés                                        |
| -------------------------------------------- | --------------------------------------------- |
| incidencia                                   | `incident`                                    |
| estado                                       | `status`                                      |
| prioridad                                    | `priority`                                    |
| solicitante                                  | `requester` (`requester_name`)                |
| responsable / asignado                       | `assignee` / `assigned_to`                    |
| comentario                                   | `comment` (`incident_comments`)               |
| registro / traza / historial                 | `log` (`incident_logs`)                       |
| acción                                       | `action`                                      |
| valor anterior / nuevo                       | `old_value` / `new_value`                     |
| autor                                        | `author` (`author_name`)                      |
| actor / usuario                              | `user_name`                                   |
| abierta / en revisión / bloqueada / resuelta | `open` / `in_review` / `blocked` / `resolved` |
| reapertura                                   | `reopened`                                    |
| transición                                   | `transition`                                  |
| diagnóstico / corregir                       | `diagnose` / `fix`                            |

## Estructura de carpetas

- **Dominio backend:** `app/Models`, `app/Enums` (estados/prioridad), `app/Services`
  (lógica de transición y logging), `app/Http/Controllers`, `app/Http/Requests`
  (validación), `app/Console/Commands` (diagnóstico).
- **Frontend:** `resources/js/pages/incidents/*` (pantallas Inertia),
  `resources/js/components/incidents/*` (piezas reutilizables), tipos en
  `resources/js/types`.
- **Rutas:** `routes/web.php`, todas con `->name('incidents.*')`.

## Principios

- Centralizar estados y prioridades en **enums PHP** (nada de strings mágicos sueltos).
- Toda mutación que afecte a la incidencia **y** su traza va en una transacción.
- Mensajes de error claros para el usuario; nunca volcar excepciones crudas a la UI.
