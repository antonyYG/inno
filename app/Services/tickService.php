<?php
namespace App\Services;

use App\Models\Asistencia;

class tickService
{
    public function getInformation()
    {
        $asistencia = Asistencia::where('usuario_id', auth()->id())
            ->whereDate('fecha', today())
            ->first();

        $asistenciaSemana = Asistencia::where('usuario_id', auth()->id())
            ->whereBetween('fecha', [
                today()->startOfWeek(),
                today()->endOfWeek()
            ])
            ->select([
                'fecha',
                'tipo',
                'tiempo_trabajado'
            ])
            ->orderBy('fecha')
            ->get();

        return [
            'asistencia' => $asistencia,
            'asistenciaSemana' => $asistenciaSemana,
        ];
    }
}

