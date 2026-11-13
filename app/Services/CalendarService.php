<?php

namespace App\Services;

use App\Models\Asistencia;
use Illuminate\Support\Facades\Storage;

class CalendarService
{
    private const COLORS = [
        'asistio' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#14532d'],
        'tardanza' => ['bg' => '#ffedd5', 'border' => '#ea580c', 'text' => '#7c2d12'],
        'falto' => ['bg' => '#fee2e2', 'border' => '#dc2626', 'text' => '#7f1d1d'],
        'pendiente' => ['bg' => '#fef9c3', 'border' => '#ca8a04', 'text' => '#713f12'],
        'justificado' => ['bg' => '#f3e8ff', 'border' => '#9333ea', 'text' => '#581c87'],
    ];

    public function getAttendances()
    {
        $query = Asistencia::with(['usuario', 'justificaciones']);

        if (! auth()->user()->hasRole('admin')) {
            $query->where('usuario_id', auth()->id());
        }

        return $query->get()->map(function ($attendance) {
            $tipo = $attendance->tipo->value;
            $usuario = $attendance->usuario?->nombre.' '.$attendance->usuario?->apellidos;
            $usuario = trim($usuario) ?: 'Usuario desconocido';
            $colors = self::COLORS[$tipo] ?? self::COLORS['pendiente'];

            // Una falta puede no tener hora de ingreso: se muestra como evento de día completo.
            $hasStart = ! empty($attendance->hora_inicio);

            // Mapeo común para evidencias y justificaciones.
            $mapear = fn ($justificacion) => [
                'id' => $justificacion->id,
                'nombre' => basename($justificacion->archivo_path),
                'descripcion' => $justificacion->descripcion,
                'estado' => $justificacion->estado,
                'observacion' => $justificacion->observacion,
                'url' => Storage::url($justificacion->archivo_path),
            ];

            $evidencias = $attendance->justificaciones
                ->where('tipo', 'evidencia')
                ->map($mapear)
                ->values();

            $justificaciones = $attendance->justificaciones
                ->where('tipo', 'justificacion')
                ->map($mapear)
                ->values();

            // Estado de revisión para el panel del administrador:
            // pendiente | aprobado | rechazado | null (ya resuelto sin revisión pendiente)
            $estadoRevision = 'pendiente';
            $estadosEvidencia = $evidencias->pluck('estado');
            $estadosJustificacion = $justificaciones->pluck('estado');

            if ($estadosEvidencia->contains('pendiente') || $estadosJustificacion->contains('pendiente')) {
                $estadoRevision = 'pendiente';
            } elseif ($estadosEvidencia->contains('aprobada') || $estadosJustificacion->contains('aprobada')) {
                $estadoRevision = 'aprobado';
            } elseif ($estadosEvidencia->contains('rechazada') || $estadosJustificacion->contains('rechazada')) {
                $estadoRevision = 'rechazado';
            } elseif (! in_array($tipo, ['pendiente', 'tardanza'], true)) {
                $estadoRevision = null;
            }

            return [
                'id' => $attendance->id,
                'title' => $usuario.' - '.ucfirst($tipo),

                'start' => $hasStart
                    ? $attendance->fecha.'T'.$attendance->hora_inicio
                    : $attendance->fecha,

                'end' => ($hasStart && $attendance->hora_fin)
                    ? $attendance->fecha.'T'.$attendance->hora_fin
                    : null,

                'allDay' => ! $hasStart,

                'backgroundColor' => $colors['bg'],
                'borderColor' => $colors['border'],
                'textColor' => $colors['text'],

                'extendedProps' => [
                    'usuario' => $usuario,
                    'tipo' => $tipo,
                    'estado' => $estadoRevision,
                    'fecha' => $attendance->fecha,
                    'hora_inicio' => $attendance->hora_inicio,
                    'hora_fin' => $attendance->hora_fin,
                    'tiempo_trabajado' => $attendance->tiempo_trabajado
                        ? substr($attendance->tiempo_trabajado, 0, 8)
                        : null,

                    // Evidencias de la jornada
                    'evidencias' => $evidencias,

                    // Justificaciones de la jornada
                    'justificaciones' => $justificaciones,

                    // Atajo para la vista del practicante: la justificación más reciente
                    'justificacion' => $justificaciones->first(),
                ],
            ];
        });
    }
}
