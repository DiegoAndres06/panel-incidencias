<?php

namespace App\Models;

use Database\Factories\AssigneeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Responsable al que se puede asignar una incidencia (catálogo).
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
class Assignee extends Model
{
    /** @use HasFactory<AssigneeFactory> */
    use HasFactory;

    /**
     * Nombres de todos los responsables, ordenados alfabéticamente.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return static::query()->orderBy('name')->pluck('name')->all();
    }
}
