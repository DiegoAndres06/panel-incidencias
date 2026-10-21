# Panel Operativo de Incidencias

Pequeña aplicación para **consultar y operar incidencias**: alta con validación, listado
paginado con filtros (estado, prioridad, texto, responsable y solicitante), cambio de estado
respetando transiciones permitidas, reasignación de responsable, historial de cambios
trazable, comentarios de seguimiento y un comando de diagnóstico de inconsistencias de datos.

Construida con **PHP + Laravel + MySQL** y frontend **Inertia + React + Tailwind**.

> El diseño detallado de cada parte (modelo de datos, máquina de estados, UI y diagnóstico)
> está documentado en [`.claude/skills/`](.claude/skills/README.md), que sirvió de guía de
> implementación.

---

## Stack

- **Backend:** PHP 8.4 · Laravel 13
- **Base de datos:** MySQL 8
- **Frontend:** Inertia 3 + React 19 + TypeScript, Tailwind v4, Vite

---

## Requisitos

| Herramienta | Versión                                                                                                               |
| ----------- | --------------------------------------------------------------------------------------------------------------------- |
| PHP         | **8.3+** (recomendado 8.4) con extensiones `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `ctype`, `json`, `bcmath` |
| Composer    | 2.x                                                                                                                   |
| Node.js     | **20+** (recomendado 22)                                                                                              |
| npm         | 10+                                                                                                                   |
| MySQL       | **8.x**                                                                                                               |

---

## Instalación

### 1. Base de datos

Hace falta una base de datos MySQL 8 vacía (por defecto se llama `incidencias`) y un usuario
con acceso a ella. Por ejemplo, desde el cliente de MySQL:

```sql
CREATE DATABASE incidencias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'incidencias'@'localhost' IDENTIFIED BY 'tu-contraseña';
GRANT ALL PRIVILEGES ON incidencias.* TO 'incidencias'@'localhost';
```

### 2. Aplicación

```bash
git clone <url-del-repo> panel-incidencias
cd panel-incidencias

composer install
cp .env.example .env
php artisan key:generate
```

Edita `.env` y completa `DB_PASSWORD` con la contraseña del usuario que acabas de crear. Si tu
MySQL es distinto, ajusta también `DB_HOST`, `DB_PORT`, `DB_DATABASE` y `DB_USERNAME`.
Después:

```bash
php artisan migrate --seed      # crea el esquema y carga datos de ejemplo
npm install
npm run build                   # o `npm run dev` durante el desarrollo
```

## Migrar y cargar datos de ejemplo

```bash
php artisan migrate --seed
```

El seeder carga datos **ficticios**:

- **El catálogo de responsables** (`assignees`): las personas a las que se puede asignar una
  incidencia. Se completa siempre, y solo crea los que faltan.
- **7 incidencias de ejemplo** con un historial coherente, creadas a través del mismo servicio
  que usa la aplicación: abiertas, en revisión, bloqueadas, resueltas, una reabierta y otra
  con un bloqueo y su desbloqueo, con sus comentarios.
- **3 incidencias con datos incoherentes a propósito**, para practicar el diagnóstico del
  Ejercicio 5 (ver más abajo).

---

## Levantar la aplicación

```bash
composer dev
```

Arranca en un solo comando el servidor de Laravel, Vite, la cola y el visor de logs
(`php artisan dev:list` muestra los procesos). La app queda en **http://localhost:8000**.

---

## Diagnóstico de inconsistencias (Ejercicio 5)

```bash
php artisan incidents:diagnose          # solo informa (dry-run)
php artisan incidents:diagnose --fix    # aplica las correcciones seguras
php artisan incidents:diagnose --sql    # además muestra las consultas de detección
```

Sin `--fix` no se escribe nada: el comando muestra lo que encuentra y lo que haría. Termina con
código 1 si queda algo pendiente.

`php artisan migrate --seed` deja tres incidencias con datos incoherentes, escritos a mano en
las tablas (la aplicación no puede producirlos porque todo pasa por `IncidentService`):

| Incidencia                                          | Problema                                                            |
| --------------------------------------------------- | ------------------------------------------------------------------- |
| «Falla de sincronización con el ERP»                | Está resuelta, pero falta el log de resolución.                     |
| «Alertas de disco lleno en el servidor de archivos» | Su estado es «Bloqueada», pero el último log la dejó «En revisión». |
| «Cambios de turno sin reflejar en el calendario»    | El log «abierta → en revisión» está dos veces, idéntico.            |

### Consultas

Cada comprobación es una clase en `app/Services/Diagnosis/Checks/`, y su método `query()` es la
consulta que se ejecuta de verdad.

**1. Resuelta sin log de resolución**

```sql
SELECT * FROM incidents i
WHERE i.status = 'resolved'
  AND NOT EXISTS (SELECT 1 FROM incident_logs l
                  WHERE l.incident_id = i.id AND l.new_value = 'resolved');
```

Toda incidencia que llega a «resuelta» desde la aplicación deja un log con
`new_value = 'resolved'`. Si no hay ninguno, el estado cambió sin dejar rastro.

**2. Estado distinto del último log**

```sql
SELECT i.* FROM incidents i
WHERE i.status <> (SELECT l.new_value FROM incident_logs l
                   WHERE l.incident_id = i.id
                     AND l.action IN ('created', 'status_changed', 'reopened')
                   ORDER BY l.created_at DESC, l.id DESC LIMIT 1);
```

El estado tiene que ser el que dejó el último cambio registrado (los comentarios no cuentan).
Esta es una versión simplificada: la real excluye además las resueltas sin log de resolución,
que ya salen en la comprobación 1.

**3. Logs duplicados**

```sql
SELECT incident_id, action, old_value, new_value, user_name, created_at, COUNT(*) AS copies
FROM incident_logs
GROUP BY incident_id, action, old_value, new_value, user_name, created_at
HAVING COUNT(*) > 1;
```

La aplicación no puede escribir dos veces el mismo cambio, así que dos logs idénticos en el
mismo instante sobran. Después, en PHP, se descarta la repetición legítima (por ejemplo,
«en revisión → bloqueada» dos veces con un desbloqueo en medio): solo cuenta como copia un log
idéntico al anterior.

### Qué se corrige

- **1:** con `--fix` crea un log de resolución marcado como reconstruido
  (`metadata.reconstructed`) y sin autor, pero solo si resolver desde el último estado
  registrado es una transición válida. Si no lo es, queda para revisión manual.
- **2:** no se corrige nunca. No se sabe si el error está en el estado o en el historial, y
  cualquier elección sería adivinar. Solo se informa.
- **3:** elimina las copias y conserva la primera.

Cada reparación va en una transacción y vuelve a comprobar el problema antes de escribir, así
que ejecutar `--fix` dos veces no duplica nada.

---

## Tests

```bash
php artisan test
```

---

## Decisiones técnicas

**Estados y transiciones.** Los estados, las prioridades y los tipos de log son enums de PHP,
así que no hay textos sueltos repartidos por el código. Las transiciones permitidas están en el
enum del estado y solo se aplican desde `IncidentService`. La interfaz muestra únicamente los
estados permitidos, y el servidor vuelve a validar al recibir el cambio.

**Transacciones.** Crear, cambiar de estado, reasignar y comentar guardan el cambio y su
registro en `incident_logs` en una sola transacción. Si falla el registro no queda nada a
medias, y hay tests que lo comprueban. Al cambiar de estado o de responsable se vuelve a leer
la incidencia con bloqueo de fila, para que dos personas a la vez no se pisen.

**Historial.** `incident_logs` solo se inserta, nunca se modifica. Una incidencia resuelta no
vuelve directamente a abierta: se reabre a «en revisión», con un motivo obligatorio, y queda un
log `reopened`.

**Quién hace el cambio.** Como no hay login, quien opera escribe su nombre al cambiar el estado,
reasignar o comentar, y se guarda en el historial. Es un texto, no una identidad verificada. El
último nombre usado se recuerda en el navegador.

**Responsables.** Hay una tabla `assignees` con los responsables (la llena el seeder) y se elige
de esa lista al crear o reasignar. `incidents.assigned_to` guarda el nombre, como pide el
enunciado, sin clave foránea: así no hubo que convertir una columna con datos. A cambio,
renombrar a un responsable no actualiza las incidencias antiguas. Los solicitantes son texto
libre.

**Listado.** Los filtros (estado, prioridad, texto, responsable y solicitante) y la página van en
la URL y se aplican sin recargar la página entera. La búsqueda trata `%` y `_` como texto normal.
Los contadores por estado respetan todos los filtros menos el de estado, y se pagina de 10 en 10.
Resolver o bloquear pide confirmación y las incidencias bloqueadas se resaltan. No hay «vencidas»
porque el modelo no tiene fecha de vencimiento.

**Validación.** Un `FormRequest` por acción, con los mensajes en español en
`lang/es/validation.php`. Los errores del servicio (transición no permitida, falta de motivo)
salen bajo el campo igual que los de validación.

**Diagnóstico.** Dry-run por defecto. Solo se corrige lo que tiene información suficiente; lo
ambiguo se deja para revisión manual (el detalle está en la sección «Diagnóstico de inconsistencias»).

**Índices y datos de ejemplo.** Los índices cubren los filtros habituales, sin duplicados. El
seeder no siembra incidencias si ya hay alguna.
