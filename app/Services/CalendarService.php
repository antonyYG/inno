<?php

namespace App\Services;

use App\Models\Asistencia;
use Illuminate\Support\Facades\Storage;

class CalendarService
{
     private const COLORS = [
        'asistio'   => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#14532d'],
        'tardanza'  => ['bg' => '#ffedd5', 'border' => '#ea580c', 'text' => '#7c2d12'],
        'falto'     => ['bg' => '#fee2e2', 'border' => '#dc2626', 'text' => '#7f1d1d'],
        'pendiente' => ['bg' => '#fef9c3', 'border' => '#ca8a04', 'text' => '#713f12'],
    ];

    public function getAttendances()
    {
        $query = Asistencia::with(['usuario','justificaciones']);

        if (!auth()->user()->hasRole('admin')) {
            $query->where('usuario_id', auth()->id());
        }

        return $query->get()->map(function ($attendance) {
            $tipo    = $attendance->tipo->value;
            $usuario = $attendance->usuario?->nombre ?? 'Usuario desconocido';
            $colors  = self::COLORS[$tipo] ?? self::COLORS['pendiente'];

            // Una falta puede no tener hora de ingreso: se muestra como evento de día completo.
            $hasStart = !empty($attendance->hora_inicio);

            return [
                'id'    => $attendance->id,
                'title' => $usuario . ' - ' . ucfirst($tipo),

                'start' => $hasStart
                    ? $attendance->fecha . 'T' . $attendance->hora_inicio
                    : $attendance->fecha,

                'end' => ($hasStart && $attendance->hora_fin)
                    ? $attendance->fecha . 'T' . $attendance->hora_fin
                    : null,

                'allDay' => !$hasStart,

                'backgroundColor' => $colors['bg'],
                'borderColor'     => $colors['border'],
                'textColor'       => $colors['text'],

                'extendedProps' => [
                    'usuario'     => $usuario,
                    'tipo'        => $tipo,
                    'fecha'       => $attendance->fecha,
                    'hora_inicio' => $attendance->hora_inicio,
                    'hora_fin'    => $attendance->hora_fin,

                    // Evidencias de la jornada
                    'evidencias' => $attendance->justificaciones->where('tipo', 'evidencia')->map(fn ($justificacion) => [
                        'id'          => $justificacion->id,
                        'nombre'      => $justificacion->nombre,
                        'descripcion' => $justificacion->descripcion,
                        'url'         => Storage::url($justificacion->archivo_path),
                    ])->values(),

                    'justificaciones' => $attendance->justificaciones->where('tipo', 'justificacion')->map(fn ($justificacion) => [
                        'id'          => $justificacion->id,
                        'nombre'      => $justificacion->nombre,
                        'descripcion' => $justificacion->descripcion,
                        'url'         => Storage::url($justificacion->archivo_path),
                    ])->values(),

                ],
            ];
        });
    }
}
