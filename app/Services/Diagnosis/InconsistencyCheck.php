<?php

namespace App\Services\Diagnosis;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Una comprobación de coherencia sobre los datos de las incidencias.
 *
 * @template TModel of Model
 */
interface InconsistencyCheck
{
    /**
     * Nombre corto de la comprobación.
     */
    public function title(): string;

    /**
     * Qué detecta y por qué la consulta lo detecta.
     */
    public function explanation(): string;

    /**
     * La consulta de detección. Es la misma que usa `findings()`, así que lo que se
     * muestra con `--sql` es siempre lo que realmente se ejecuta.
     *
     * @return Builder<TModel>
     */
    public function query(): Builder;

    /**
     * @return Collection<int, Finding>
     */
    public function findings(): Collection;
}
