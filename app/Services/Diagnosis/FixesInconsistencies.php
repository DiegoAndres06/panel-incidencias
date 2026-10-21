<?php

namespace App\Services\Diagnosis;

/**
 * Una comprobación que sabe corregir sus hallazgos automáticos.
 */
interface FixesInconsistencies
{
    /**
     * Corrige un hallazgo automático dentro de una transacción. Antes de escribir vuelve a
     * comprobar que el problema sigue existiendo, así que repetirlo no hace nada.
     *
     * @return string|null Lo que se hizo, o null si no hizo falta o ya no aplicaba.
     */
    public function fix(Finding $finding): ?string;
}
