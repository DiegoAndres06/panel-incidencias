---
name: data-diagnosis
description: Ejercicio 5 — diagnóstico y corrección segura de inconsistencias de datos mediante el comando incidents:diagnose (dry-run por defecto). Define las comprobaciones, la estrategia de corrección prudente y los seeders de datos incoherentes. Leer antes de tocar el comando, las comprobaciones o los seeders.
---

# Diagnóstico de inconsistencias (Ejercicio 5)

## Comando

`app/Console/Commands/DiagnoseIncidents.php`

```
php artisan incidents:diagnose          # dry-run (por defecto): solo informa
php artisan incidents:diagnose --fix    # aplica las correcciones seguras
php artisan incidents:diagnose --sql    # muestra la consulta de detección de cada comprobación
```

- **Sin `--fix` nunca escribe.** Con `--fix` aplica solo lo marcado como automático.
- Código de salida `0` si no queda nada pendiente, `1` si queda algo (en dry-run cualquier
  hallazgo; con `--fix`, lo de revisión manual u omitido). Sirve para scripts o CI.

## Arquitectura (`app/Services/Diagnosis`)

- `InconsistencyCheck` (genérica en el modelo): `title()`, `explanation()` (qué detecta y por
  qué), `query()` (la consulta **real** de detección; `--sql` imprime `toRawSql()` de esa misma
  consulta, así documentación y código no se desfasan) y `findings()`.
- `FixesInconsistencies`: `fix(Finding): ?string`. Solo la implementan las comprobaciones con
  correcciones automáticas. Devuelve lo que hizo o `null` si ya no aplicaba.
- `Finding` + `Resolution` (`Automatic` | `ManualReview`): incidencia, resumen, explicación
  de qué se haría o por qué no se corrige, y contexto para la reparación.
- Comprobaciones en `Checks/`: `ResolvedWithoutResolutionLog`, `StatusMismatchesLastLog`,
  `DuplicateLogs`. Para añadir una nueva: implementar la interfaz y registrarla en
  `DiagnoseIncidents::checks()`.

## Las tres comprobaciones

1. **Resuelta sin log de resolución:** `status = resolved` y no existe ningún log con
   `new_value = resolved`.
2. **Estado distinto del último log de estado:** compara `incidents.status` con el `new_value`
   del último log de las acciones `created`, `status_changed` y `reopened` (los comentarios no
   cuentan; el orden es `created_at DESC, id DESC`). Excluye las resueltas sin log de resolución
   (las reporta la 1) y las incidencias sin logs de estado (no hay con qué comparar).
3. **Logs duplicados:** SQL con `GROUP BY` de todos los campos + `HAVING COUNT(*) > 1`; después,
   en PHP, se descarta la repetición legítima (un log de estado solo es copia si es idéntico al
   anterior inmediato; los comentarios, si ya hubo uno idéntico) y se comparan los metadatos
   normalizados (MySQL no conserva el orden de las claves JSON).

## Estrategia de corrección (prudencia ante todo)

| Caso                                                        | Con `--fix`                                                                                                                                          | Por qué                                                                                                                         |
| ----------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| 1. Resuelta sin log, historial compatible (o sin historial) | Crea un log de resolución **reconstruido**: `metadata.reconstructed = true`, sin autor, fechado en `updated_at` de la incidencia. No toca el estado. | Se conoce el estado final y resolver desde el último estado registrado es una transición válida. Es identificable y reversible. |
| 1. Historial incompatible (p. ej. último log «abierta»)     | Nada; revisión manual.                                                                                                                               | El historial no explica la resolución; reconstruir sería inventar.                                                              |
| 2. Estado ≠ último log                                      | **Nunca** se corrige sola; revisión manual.                                                                                                          | No se sabe qué lado es el correcto.                                                                                             |
| 3. Duplicados                                               | Borra las copias y conserva el primer log.                                                                                                           | Idénticas en todo: no se pierde información. Se re-verifica antes de borrar.                                                    |

- Toda reparación va en `DB::transaction` y **re-comprueba el problema antes de escribir**
  (idempotente: ejecutar `--fix` dos veces no duplica nada).
- Los casos manuales solo se informan; no se persiste ninguna marca (exigiría una columna o un
  log inventado).

## Seeders

- `DatabaseSeeder` no siembra si ya hay incidencias.
- `IncidentSampleSeeder`: 7 incidencias coherentes, creadas con `IncidentService` y fechas
  escalonadas (`Date::setTestNow`, restaurado en `finally`).
- `InconsistentIncidentsSeeder`: las 3 incidencias incoherentes, escritas directamente en las
  tablas. Sus títulos son constantes públicas que usan los tests (no hay literales duplicados).

## Pruebas

- Un test por comportamiento de cada comprobación, incluidos los falsos positivos que hay que
  evitar (transición repetida legítima, comentarios, logs iguales en incidencias distintas) y
  la idempotencia de `--fix`.
- Prueba de mutación: romper a propósito cada decisión (guarda de transición, re-verificación,
  exclusión entre comprobaciones, filtro de acciones, adyacencia, metadatos, dry-run) debe
  hacer fallar algún test.
