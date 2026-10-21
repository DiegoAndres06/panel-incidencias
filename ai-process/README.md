# Uso de IA en el desarrollo

Para desarrollar esta prueba utilicé Claude Code como asistente principal. La IA generó la mayor parte del código, mientras que yo definí el alcance, revisé las propuestas, aprobé la planificación de cada ejercicio y comprobé el resultado mediante pruebas manuales y automatizadas.

## 1. Herramientas utilizadas

- **IA:** Claude Code, con los modelos Claude Opus 4.8 y Claude Sonnet 5.5.
- **Backend:** Laravel, PHP, Composer, Artisan y PHPUnit.
- **Frontend:** Inertia, React, Tailwind CSS, TypeScript y Vite.
- **Base de datos y entorno:** MySQL en Docker y SQLite en memoria para los tests.

## 2. Prompts principales

El trabajo se realizó en una conversación en español, avanzando ejercicio por ejercicio.

- **Configuración:** creación del proyecto con Laravel, Tailwind e Inertia con React, y preparación de una carpeta de skills con las convenciones del proyecto, el modelo de datos, las transiciones de estado, la interfaz, el diagnóstico y las pruebas.
- **Planificación:** antes de implementar cada ejercicio, utilicé el modo de planificación de Claude Code para analizar los requisitos y proponer una solución. Revisé las propuestas, realicé los ajustes necesarios y aprobé la planificación antes de comenzar a programar.
- **Implementación:** instrucciones específicas para desarrollar cada ejercicio, respetar su alcance y mantener la coherencia con los requisitos de la prueba.
- **Revisión y entrega:** solicitudes para ejecutar comprobaciones, corregir errores, revisar la documentación y comprobar qué aspectos quedaban pendientes.

Separar el trabajo de esta manera me permitió mantener el contexto de cada ejercicio más acotado y evitar mezclar decisiones de distintas partes de la aplicación. También facilitó la trazabilidad de los prompts, las decisiones y las correcciones, ya que cada ejercicio tenía su propio historial de trabajo.

Además, este enfoque ayudó a que la implementación partiera del estado real del repositorio y de las instrucciones documentadas en las skills, en lugar de depender únicamente de decisiones tomadas en conversaciones anteriores. Así resultaba más sencillo revisar un ejercicio, introducir cambios o rehacer una parte sin afectar directamente al proceso de los demás.

## 3. Partes generadas con ayuda de IA

- **Backend:** migraciones, modelos, factories, `IncidentService`, validaciones, controladores, rutas, enums, el comando `incidents:diagnose` y los seeders.
- **Frontend:** páginas y componentes para el listado de incidencias, filtros, contadores, paginación, formularios, diálogos, cambios de estado, comentarios e historial. El sidebar fue el elemento de la interfaz que modifiqué directamente.
- **Pruebas y documentación:** 14 archivos de PHPUnit con 149 tests, las skills del proyecto, el README y los documentos de uso de IA.

El desarrollo fue iterativo. Después de implementar cada módulo, la IA resumía los cambios realizados y las comprobaciones ejecutadas, indicando también lo que no había podido verificar. Yo revisaba los módulos, comprobaba su coherencia con los requisitos y probaba manualmente las funcionalidades. También utilizaba los tests unitarios para detectar errores y verificar los comportamientos esperados.

## 4. Cambios, correcciones y decisiones tomadas manualmente

Las principales decisiones de alcance que tomé fueron:

- Elegir Tailwind, Inertia con React y MySQL en Docker.
- Retirar la autenticación del frontend y del backend para evitar la redirección al login, ya que no era un requisito de la prueba, y hacer que la raíz mostrara el listado de incidencias.
- Utilizar el campo «Tu nombre» como actor de los cambios, recordando el valor en el navegador al no haber autenticación.
- Incorporar una tabla de responsables con seeder, un catálogo basado en nombres sin clave foránea, la reasignación de incidencias y la paginación.
- Ejecutar personalmente las migraciones, los seeders y la instalación de los drivers de PHP. La IA realizó consultas de lectura (`SELECT`) sobre la base de datos.

También revisé y mantuve varias propuestas técnicas de la IA, como utilizar transacciones y bloqueo de filas al cambiar estados, exigir un motivo para reabrir incidencias y aplicar una política prudente al diagnosticar inconsistencias. En particular, el diagnóstico no corrige automáticamente un estado que no coincide con el último registro del historial, porque no hay información suficiente para determinar cuál de los dos es incorrecto.

## 5. Errores detectados en la salida de la IA y cómo los corregí

Durante el desarrollo se detectaron problemas mediante las herramientas de validación, la revisión del código y las comprobaciones realizadas por la propia IA.

- **Tipado de TypeScript:** al eliminar la autenticación, `global.d.ts` dejó de ser un módulo y provocó errores en diez archivos. Se corrigió añadiendo `export {}`.
- **Consultas SQL:** PHPStan rechazó una consulta con SQL interpolado. Se sustituyó por una comparación mediante una subconsulta de Eloquent. Además, se cambió el carácter de escape de las consultas `LIKE` para evitar diferencias entre MySQL y SQLite.
- **Cobertura de las pruebas:** una prueba de mutación mostró que dos de ocho modificaciones intencionadas no provocaban fallos en los tests. Se añadieron dos pruebas para cubrir los casos que estaban pasando inadvertidos.
- **Diagnóstico de inconsistencias:** la detección inicial confundía transiciones repetidas legítimas con duplicados y podía informar dos veces de una misma incidencia. Se limitó la detección de duplicados a registros consecutivos y se corrigió el solapamiento entre comprobaciones.
- **Confirmaciones en el frontend:** pulsar Intro podía enviar el formulario sin mostrar la confirmación de la acción. Se interceptó el envío para evitarlo.
- **Documentación y configuración:** se detectaron referencias incorrectas a Inertia 2 y Pest, cuando el proyecto utiliza Inertia 3 y PHPUnit. También se comprobó que el `.gitignore` excluía las skills que el README enlazaba.