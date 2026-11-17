<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Garantiza una sola asistencia por usuario y fecha.
     * Verificado antes de ejecutar: no existen duplicados (usuario_id, fecha).
     */
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->unique(['usuario_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropUnique(['usuario_id', 'fecha']);
        });
    }
};
