<?php

namespace App\Services;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Registra las decisiones sobre justificaciones usando únicamente Spatie Activitylog.
 */
class AuditoriaJustificacionService
{
    private const EVENTO_ACEPTADA = 'accepted';

    private const EVENTO_RECHAZADA = 'rejected';

    public function registrarAceptacion(Asistencia $asistencia, ?string $observacion): void
    {
        $this->registrarDecision(
            $asistencia,
            self::EVENTO_ACEPTADA,
            'aprobada',
            $observacion
        );
    }

    public function registrarRechazo(Asistencia $asistencia, ?string $observacion): void
    {
        $this->registrarDecision(
            $asistencia,
            self::EVENTO_RECHAZADA,
            'rechazada',
            $observacion
        );
    }

    private function registrarDecision(
        Asistencia $asistencia,
        string $evento,
        string $nuevoEstado,
        ?string $observacion
    ): void {
        if (! in_array($evento, [self::EVENTO_ACEPTADA, self::EVENTO_RECHAZADA], true)) {
            throw new InvalidArgumentException('La decisión de auditoría no es válida.');
        }

        $administrador = auth()->user();

        if (! $administrador instanceof User) {
            throw new AuthenticationException;
        }

        if (! $administrador->hasRole('admin')) {
            throw new AuthorizationException;
        }

        $tipoNuevo = $this->tipoAsistenciaTrasDecision($asistencia, $evento);

        DB::transaction(function () use ($asistencia, $administrador, $evento, $nuevoEstado, $observacion, $tipoNuevo): void {
            $asistencia->update(['tipo' => $tipoNuevo]);

            foreach ($this->justificacionesARevisar($asistencia) as $justificacion) {
                $estadoAnterior = $justificacion->estado;

                $justificacion->update([
                    'estado' => $nuevoEstado,
                    'observacion' => $observacion,
                ]);

                activity('justificaciones')
                    ->performedOn($justificacion)
                    ->by($administrador)
                    ->event($evento)
                    ->withProperties([
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $nuevoEstado,
                        'observacion' => $observacion,
                        'asistencia_id' => $asistencia->id,
                    ])
                    ->log($evento === self::EVENTO_ACEPTADA
                        ? 'Justificación aceptada.'
                        : 'Justificación rechazada.');
            }
        });
    }

    private function tipoAsistenciaTrasDecision(Asistencia $asistencia, string $evento): EstadoAsistenciaEnum
    {
        $esTardanza = $asistencia->tipo === EstadoAsistenciaEnum::TARDANZA;

        if ($evento === self::EVENTO_ACEPTADA) {
            return $esTardanza ? EstadoAsistenciaEnum::TARDANZA : EstadoAsistenciaEnum::JUSTIFICADO;
        }

        return $esTardanza ? EstadoAsistenciaEnum::TARDANZA : EstadoAsistenciaEnum::FALTO;
    }

    /**
     * @return Collection<int, Justificacion>
     */
    private function justificacionesARevisar(Asistencia $asistencia): Collection
    {
        $pendientes = $asistencia->justificaciones()
            ->where('tipo', 'justificacion')
            ->where('estado', 'pendiente')
            ->orderBy('id')
            ->get();

        if ($pendientes->isNotEmpty()) {
            return $pendientes;
        }

        return $asistencia->justificaciones()
            ->where('tipo', 'justificacion')
            ->orderByDesc('id')
            ->limit(1)
            ->get();
    }
}
