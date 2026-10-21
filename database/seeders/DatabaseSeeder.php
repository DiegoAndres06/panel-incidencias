<?php

namespace Database\Seeders;

use App\Models\Incident;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * El catálogo de responsables se completa siempre (solo crea los que faltan). Las
     * incidencias de ejemplo, en cambio, no se siembran si ya hay incidencias, para no
     * duplicar datos ni mezclar los de ejemplo con los reales.
     */
    public function run(): void
    {
        $this->call(AssigneeSeeder::class);

        if (Incident::query()->exists()) {
            $this->command?->warn('Ya hay incidencias: no se siembran los datos de ejemplo. En desarrollo, `php artisan migrate:fresh --seed` empieza de cero.');

            return;
        }

        $this->call([
            IncidentSampleSeeder::class,
            InconsistentIncidentsSeeder::class,
        ]);
    }
}
