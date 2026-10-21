<?php

namespace Database\Seeders;

use App\Models\Assignee;
use Illuminate\Database\Seeder;

/**
 * Catálogo de responsables (datos ficticios). Se puede ejecutar varias veces: solo crea los
 * que faltan, así que también sirve para completar una base de datos que ya tiene incidencias:
 *
 *     php artisan db:seed --class=AssigneeSeeder
 */
class AssigneeSeeder extends Seeder
{
    /** @var array<int, string> */
    public const NAMES = [
        'Andrea Molina',
        'Carlos Peña',
        'Iván Calvo',
        'Laura Gómez',
        'Paula Herrera',
        'Sergio Vidal',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Assignee::query()->firstOrCreate(['name' => $name]);
        }
    }
}
