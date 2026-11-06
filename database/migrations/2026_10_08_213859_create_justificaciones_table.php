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
        Schema::create('justificaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asistencia_id')->constrained('asistencias')->onDelete('cascade');
            $table->text('descripcion');
            $table->string('archivo_path');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->enum('tipo',['evidencia','justificacion'])->default('justificacion');
            $table->date('fecha');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('justificaciones');
    }
};
