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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->text('description')->nullable();
            // Estado y prioridad son texto y no ENUM de MySQL: los valores válidos los
            // define el enum de PHP, y así añadir uno no obliga a alterar la tabla.
            $table->string('status', 20)->default('open');
            $table->string('priority', 20)->default('medium');
            $table->string('requester_name', 120);
            $table->string('assigned_to', 120)->nullable();
            $table->timestamps();

            // Listado habitual: filtrar por estado o prioridad y ordenar por fecha.
            // Cada filtro tiene su índice con la fecha como segunda columna, y un
            // índice solo por fecha cubre el listado sin filtros.
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
