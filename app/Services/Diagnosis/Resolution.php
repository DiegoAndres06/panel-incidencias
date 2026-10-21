<?php

namespace App\Services\Diagnosis;

/**
 * Qué se hace con un hallazgo del diagnóstico.
 */
enum Resolution
{
    /** Hay información suficiente para corregirlo con seguridad. */
    case Automatic;

    /** Es ambiguo: no se toca nada y una persona debe decidir. */
    case ManualReview;
}
