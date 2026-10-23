<?php

namespace App\Services;

use App\Models\Asistencia;

class CalendarService
{
    public function getAttendances()
    {
        $query = Asistencia::with('usuario');

        if (!auth()->user()->hasRole('admin')) {
            $query->where('usuario_id', auth()->id());
        }

        $attendances = $query->get();

        return $attendances->map(function ($attendance) {
            return [
                'id' => $attendance->id,
                'title' => ($attendance->usuario?->nombre ?? 'Usuario desconocido')
                    . "\n"
                    . ucfirst($attendance->tipo->value),
                'start' => $attendance->fecha . 'T' . $attendance->hora_inicio,
                'end' => $attendance->fecha . 'T' . $attendance->hora_fin,
            ];
        });
    }
}
