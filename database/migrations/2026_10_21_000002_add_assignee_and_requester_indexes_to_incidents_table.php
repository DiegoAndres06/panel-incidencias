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
        // El listado se puede filtrar por responsable y por solicitante, y siempre se
        // ordena por fecha: mismo patrón que los índices de estado y prioridad.
        Schema::table('incidents', function (Blueprint $table) {
            $table->index(['assigned_to', 'created_at']);
            $table->index(['requester_name', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['assigned_to', 'created_at']);
            $table->dropIndex(['requester_name', 'created_at']);
        });
    }
};
