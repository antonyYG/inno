<?php

namespace App\Services;

use App\Models\Justificacion;
use Illuminate\Http\Request;

class ReporteEvidenciaService
{
    public function getEvidencias(Request $request)
    {
        return Justificacion::query()
            ->with(['asistencia.usuario'])
            ->where('tipo', 'evidencia')

            ->when($request->filled('practicante'), function ($query) use ($request) {
                $query->whereHas('asistencia', function ($asistencia) use ($request) {
                    $asistencia->where('usuario_id', $request->practicante);
                });
            })

            ->when($request->filled('fechaDesde'), function ($query) use ($request) {
                $query->whereDate('fecha', '>=', $request->fechaDesde);
            })

            ->when($request->filled('fechaHasta'), function ($query) use ($request) {
                $query->whereDate('fecha', '<=', $request->fechaHasta);
            })

            ->when(
                $request->filled('estado') || $request->filled('estadoAsistencia'),
                function ($query) use ($request) {
                    $estado = $request->input('estado', $request->input('estadoAsistencia'));

                    $query->where('estado', $estado);
                }
            )

            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }
}
