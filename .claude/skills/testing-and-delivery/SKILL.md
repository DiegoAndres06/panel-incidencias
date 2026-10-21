---
name: testing-and-delivery
description: Tests mínimos y honestos (PHPUnit) y checklist de entrega del repositorio (README, .env.example, seeders, carpeta /ai-process). Leer antes de escribir tests o preparar la entrega.
---

# Tests y entrega

## Filosofía de tests (pocos, pero de verdad)

- Un test debe **fallar primero por el motivo correcto** antes de ponerse verde.
- **Nada de ids literales** ni de datos mágicos: usar factories y leer lo que el sistema
  devuelve.
- **Nada de llamadas de red** en los tests.
- **Prohibido ablandar una assertion** para forzar el verde. Si hay que cambiar una
  assertion existente, se explica.
- La prueba no pide suite extensa: se busca cubrir lo que demuestra criterio.

## Qué cubren los tests (PHPUnit, SQLite en memoria)

- Enums: mapa completo de transiciones (matriz 4×4), etiquetas y valores.
- Modelos: valores por defecto, casts, relaciones, borrado en cascada, clave foránea e índices.
- `IncidentService`: creación con su log, transiciones válidas e inválidas sin escribir nada,
  reapertura con motivo y log `reopened`, comentarios y rollback si falla el log.
- HTTP: validación en español, filtros, contadores, detalle (404 si no existe), cambio de
  estado y comentarios.
- Diagnóstico: cada comprobación (incluidos los falsos positivos a evitar), `--fix`
  idempotente, dry-run que no escribe, `--sql` y los seeders.
- Tras escribir tests de comportamiento ya existente, hacer una **prueba de mutación**: romper
  el código a propósito y comprobar que algún test falla.

> La DB de testing la gestiona PHPUnit; `php artisan test` puede usarla sin pedir permiso.
> Nunca correr `migrate:fresh` por CLI fuera de la suite.

## Checklist de entrega (Secciones 9 y 10 de la prueba)

- [x] Código fuente del proyecto.
- [x] Migraciones **y** seeders (datos de ejemplo + seeder de inconsistencias del Ej. 5).
- [x] `.env.example` sin credenciales reales, con la config de MySQL documentada.
- [x] README que permita ejecutar **sin adivinar**:
    - Requisitos: versiones de PHP, Composer, Node, MySQL.
    - Pasos de instalación (`composer install`, `npm install`, `key:generate`).
    - Comandos de migración y carga de datos de ejemplo.
    - Comando para levantar la app (artisan serve + vite / `composer dev`).
    - Comando/pasos para ejecutar el **diagnóstico de inconsistencias** (Ej. 5).
    - Indicar que no hay autenticación (no hay usuario de prueba).
    - Notas de decisiones técnicas.
- [x] Explicación de las inconsistencias simuladas y cómo se diagnostican/corrigen.
- [x] Carpeta `/ai-process` con: herramientas usadas, prompts principales, partes generadas
      con IA, decisiones/correcciones manuales, errores detectados en la salida de la IA.

## Fuera de alcance (no implementar)

Auth completa, roles/permisos avanzados, notificaciones, tests extensos, Docker como
entrega, deploy público, integraciones externas. Preferir pequeño y consistente.
