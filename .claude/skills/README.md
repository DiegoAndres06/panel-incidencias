# Skills del proyecto — Panel Operativo de Incidencias

Este directorio contiene las **skills que guían la implementación** de la prueba técnica.
Cada skill encapsula las convenciones y el checklist de un "frente" del trabajo, para que
el código salga consistente, mantenible y razonado (que es exactamente lo que valora la
prueba: criterio sobre cantidad).

## Índice

| Skill                                                   | Ejercicio   | Para qué sirve                                  |
| ------------------------------------------------------- | ----------- | ----------------------------------------------- |
| [`project-conventions`](project-conventions/SKILL.md)   | Transversal | Stack, idioma, casing, glosario, estructura     |
| [`domain-model`](domain-model/SKILL.md)                 | Ej. 2       | Migraciones, enums, FKs, índices, las 3 tablas  |
| [`state-transitions`](state-transitions/SKILL.md)       | Ej. 2/3     | Máquina de estados, transacciones, trazabilidad |
| [`inertia-react-ui`](inertia-react-ui/SKILL.md)         | Ej. 1/4     | UI Inertia+React+Tailwind, estado, interacción  |
| [`data-diagnosis`](data-diagnosis/SKILL.md)             | Ej. 5       | Comando de diagnóstico con dry-run              |
| [`testing-and-delivery`](testing-and-delivery/SKILL.md) | Entrega     | Tests mínimos y checklist de entrega            |

## Cómo se usan

- Son la **fuente de verdad** de las convenciones. Antes de tocar un frente, se lee su skill.
- También documentan el proceso: su contenido alimenta la carpeta `/ai-process` exigida.
- No sustituyen al criterio: si algo de la prueba choca con una skill, gana la prueba y se
  actualiza la skill.
