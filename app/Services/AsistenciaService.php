<?php

namespace App\Services;

use App\Models\Asistencia;

class AsistenciaService
{
    public function getAsistencia($data)
    {
        $data = Asistencia::with('usuario')
            ->when($data->filled('practicante'), function ($query) use ($data) {
                $query->where('usuario_id', $data->practicante);
            })
            ->when($data->filled('fechaDesde'), function ($query) use ($data) {
                $query->whereDate('fecha', '>=', $data->fechaDesde);
            })
            ->when($data->filled('fechaHasta'), function ($query) use ($data) {
                $query->whereDate('fecha', '<=', $data->fechaHasta);
            })
            ->when($data->filled('estadoAsistencia'), function ($query) use ($data) {
                $query->where('tipo', $data->estadoAsistencia);
            })
            ->orderByDesc('fecha')
            ->get();

        return $data;
    }
}
