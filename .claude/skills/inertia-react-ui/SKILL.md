---
name: inertia-react-ui
description: Patrones de frontend con Inertia 3 + React 19 + TypeScript + Tailwind v4 para el Panel de Incidencias — dónde vive el estado, cómo se disparan acciones, cómo se refresca la UI, y las features de interacción requeridas. Leer antes de crear páginas o componentes.
---

# UI con Inertia + React (Ej. 1/4)

## Dónde vive el estado (regla de oro)

- **Fuente de verdad = servidor.** El listado, filtros aplicados y contadores los calcula
  el controlador y llegan como props de Inertia.
- **Estado local React** solo para UI efímera: texto del buscador antes de aplicar, apertura
  de un diálogo de confirmación, fila expandida.
- Navegación/refresco con `router` de Inertia (`router.get`, `router.reload`) usando
  `preserveState` y `preserveScroll`; los filtros viajan en la query string para ser
  enlazables y recargables.

## Pantallas (`resources/js/pages/incidents/`)

- Los archivos van en minúscula/kebab y con extensión `.tsx` (como el starter): el nombre
  debe coincidir con `Inertia::render('incidents/index')`.
- `index.tsx` — cabecera (nombre + descripción funcional), listado (tabla en escritorio /
  tarjetas en móvil) y acceso a detalle. Filtros y contadores llegan en el Ej. 3/4.
- `show.tsx` — detalle con historial (`logs`) y acción de cambio de estado. Comentarios en Ej. 3.
- Alta de incidencia: diálogo (`incident-form-dialog.tsx`) abierto desde la cabecera.
- Estados, prioridades, textos y colores viven solo en `resources/js/lib/incidents/config.ts`.
- Los datos llegan como props del controlador (recursos en `app/Http/Resources`): la
  incidencia incluye `allowed_transitions`, que decide qué estados ofrece la interfaz.
- Los filtros del listado viven en la URL y se piden con `router.get(..., { only:
['incidents', 'filters'], preserveState: true })` desde `incident-filters.tsx`.

## Componentes (`resources/js/components/incidents/`)

- `status-badge` — color + icono + texto por estado (abierta/en revisión/bloqueada/resuelta),
  accesible: no depende solo del color.
- `priority-badge`, `incident-form-dialog`, `status-change-dialog`, `incident-table`,
  `incident-cards`, `incident-row-actions`, `history-timeline`. Pendientes: filtros,
  contadores y formulario de comentario.

## Formularios

- Usar el componente `<Form {...Controller.accion.form()}>` de Inertia 3 con campos `name`
  no controlados (ver `incident-form-dialog.tsx`). Wayfinder debe generarse con `--with-form`
  (`php artisan wayfinder:generate --with-form`).
- `useForm` queda para casos con estado controlado.
- Errores de validación: renderizar `errors.campo` (textos en español que manda el backend).
- Deshabilitar submit mientras `processing`.

## Features de interacción (Ej. 4)

Implementadas (la prueba pide al menos dos; se hicieron cuatro):

1. **Filtro rápido sin recargar:** `applyIncidentFilters` (`lib/incidents/visit.ts`) hace
   `router.get` con `only: ['incidents','filters','counters']`, `preserveState` y
   `preserveScroll`; lo usan `incident-filters.tsx` y `status-counters.tsx`.
2. **Confirmación antes de resolver o bloquear:** en `status-change-dialog.tsx`, en dos
   pasos dentro del mismo diálogo. Los textos viven en `confirmationFor()` (`lib/incidents/config.ts`).
   Los campos siguen montados durante la confirmación (si no, no viajarían con el envío) y se
   intercepta `onSubmitCapture`, porque `<Form>` pone su propio `onSubmit` después de los props
   y Intro en un campo se saltaría el aviso.
3. **Contador por estado:** `status-counters.tsx`. Cada contador es un botón (`aria-pressed`)
   que filtra por ese estado. El servidor los calcula con `Incident::countsByStatus()`:
   respetan prioridad y texto, no el estado.
4. **Resaltado de bloqueadas:** `HIGHLIGHT_CLASS` en `lib/incidents/config.ts` (fila de la tabla
   y tarjeta móvil). No depende solo del color: el badge ya lleva icono y texto.
5. Filtros server-side por estado, prioridad, texto libre, responsable (incluye «Sin asignar») y
   solicitante, combinables. Las listas de los desplegables llegan como props (`assignees`,
   `requesters`).
6. **Paginación:** `incident-pagination.tsx` (`IncidentPaginationBar`); la página viaja en la URL
   y `applyIncidentFilters(filters, page)` conserva los filtros. Cambiar un filtro vuelve a la
   página 1.
7. **Nombre de quien opera:** `actor-name-field.tsx` (campo «Tu nombre») lo usan el cambio de
   estado, la reasignación y los comentarios. Muestra el último nombre usado y lo guarda al
   enviar el formulario (`lib/actor-name.ts`, `localStorage` con `try/catch`). Guarda desde un
   listener de `submit` del propio campo, no con refs al `<Form>`: tipar la referencia fija el
   genérico de `<Form>` y rompe el tipado de `errors`.
8. **Reasignar responsable:** `assignee-dialog.tsx` (en el detalle) y `assignee-select.tsx`, que
   también usa el alta. «Sin asignar» usa un valor propio en el `Select` (Radix no admite valores
   vacíos) y un `<input type="hidden">` envía el campo vacío al servidor.
   No se hizo «vencidas»: el modelo no tiene fecha de vencimiento.

## Tailwind / diseño

- Responsive real: tabla con scroll/colapso en móvil o cambio a tarjetas.
- Tokens de color por estado centralizados (un mapa `status → clases`), no inline disperso.
- Accesible: foco visible, labels asociadas, contraste suficiente, estado no solo por color.

## Backend que la alimenta

- Controladores devuelven `Inertia::render('incidents/index', [...])`.
- Enums serializados con `options()` para pintar selects y badges sin duplicar textos.
