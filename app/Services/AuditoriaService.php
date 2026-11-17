<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class AuditoriaService
{
    public function registrar(
        Model $modelo,
        string $accion,
        string $descripcion,
        array $datosAnteriores = [],
        array $datosNuevos = []
    ): void {
        activity('auditoria')
            ->performedOn($modelo)
            ->event($accion)
            ->withProperties([
                'datos_anteriores' => $datosAnteriores,
                'datos_nuevos' => $datosNuevos,
            ])
            ->log($descripcion);
    }
}
