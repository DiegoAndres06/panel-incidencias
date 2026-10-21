<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Catálogo de responsables a quienes se puede asignar una incidencia. No tiene
        // clave foránea desde `incidents`: `incidents.assigned_to` guarda el nombre elegido
        // de este catálogo, igual que `incident_logs` guarda el nombre que había en cada
        // momento. El nombre es único para poder validarlo y filtrar por él.
        Schema::create('assignees', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignees');
    }
};
