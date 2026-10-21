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
        // Trazabilidad solo de inserción: no tiene updated_at porque un registro
        // nunca se modifica una vez escrito.
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('old_value', 40)->nullable();
            $table->string('new_value', 40)->nullable();
            $table->string('user_name', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Historial de una incidencia en orden cronológico. También sirve de
            // índice para la clave foránea.
            $table->index(['incident_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_logs');
    }
};
