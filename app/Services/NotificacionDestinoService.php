<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use App\Notifications\FaltaAutomaticaNotification;
use App\Notifications\SolicitudPracticaNotification;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;
use Throwable;

class NotificacionDestinoService
{
    /**
     * Resuelve el destino seguro de una notificación usando solo datos reales.
     *
     * @return array{
     *     tipo: string,
     *     etiqueta: string,
     *     icono: string,
     *     url: string,
     *     asistencia_id: int|string|null,
     *     justificacion_id: int|string|null,
     *     fecha: string|null
     * }
     */
    public function resolver(DatabaseNotification $notificacion, User $usuario): array
    {
        $datos = is_array($notificacion->data) ? $notificacion->data : [];
        $tipo = $this->tipo($notificacion, $datos);
        $presentacion = $this->presentacion($tipo);
        $destino = $this->destino($tipo, $datos, $usuario);

        return [
            'tipo' => $tipo,
            'etiqueta' => $presentacion['etiqueta'],
            'icono' => $presentacion['icono'],
            'url' => $destino['url'],
            'asistencia_id' => $destino['asistencia_id'],
            'justificacion_id' => $destino['justificacion_id'],
            'fecha' => $destino['fecha'],
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function tipo(DatabaseNotification $notificacion, array $datos): string
    {
        if ($notificacion->type === SolicitudPracticaNotification::class) {
            $tipoSolicitud = $datos['tipo'] ?? null;

            return in_array($tipoSolicitud, ['evidencia', 'justificacion'], true)
                ? $tipoSolicitud
                : 'justificacion';
        }

        if ($notificacion->type === FaltaAutomaticaNotification::class) {
            return 'falta';
        }

        $tipo = $datos['tipo'] ?? null;

        return in_array($tipo, ['evidencia', 'justificacion', 'falta', 'certificado'], true)
            ? $tipo
            : 'general';
    }

    /**
     * @return array{etiqueta: string, icono: string}
     */
    private function presentacion(string $tipo): array
    {
        return match ($tipo) {
            'falta' => ['etiqueta' => 'Asistencia', 'icono' => 'fa-solid fa-calendar-xmark'],
            'evidencia' => ['etiqueta' => 'Evidencia', 'icono' => 'fa-solid fa-file-image'],
            'justificacion' => ['etiqueta' => 'Justificación', 'icono' => 'fa-solid fa-file-circle-check'],
            'certificado' => ['etiqueta' => 'Certificado', 'icono' => 'fa-solid fa-award'],
            default => ['etiqueta' => 'Sistema', 'icono' => 'fa-solid fa-bell'],
        };
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{
     *     url: string,
     *     asistencia_id: int|string|null,
     *     justificacion_id: int|string|null,
     *     fecha: string|null
     * }
     */
    private function destino(string $tipo, array $datos, User $usuario): array
    {
        $destinoSeguro = [
            'url' => $this->urlGeneral(),
            'asistencia_id' => null,
            'justificacion_id' => null,
            'fecha' => null,
        ];

        if (! $usuario->hasRole('admin')) {
            return $destinoSeguro;
        }

        if ($tipo === 'falta') {
            $asistencia = $this->primeraAsistencia($datos);

            return [
                'url' => $this->urlCalendario($this->parametrosCalendario($asistencia, $datos)),
                'asistencia_id' => $asistencia?->id,
                'justificacion_id' => null,
                'fecha' => $this->fechaCalendario($datos, $asistencia),
            ];
        }

        if (in_array($tipo, ['evidencia', 'justificacion'], true)) {
            $asistencia = isset($datos['asistencia_id'])
                ? Asistencia::find($datos['asistencia_id'])
                : null;

            if ($asistencia === null) {
                return $destinoSeguro;
            }

            $justificacion = isset($datos['justificacion_id'])
                ? Justificacion::find($datos['justificacion_id'])
                : null;

            if ($justificacion !== null && $justificacion->asistencia_id !== $asistencia->id) {
                $justificacion = null;
            }

            $parametros = $this->parametrosCalendario($asistencia, $datos);

            if ($justificacion !== null) {
                $parametros['registro'] = $justificacion->id;
            }

            return [
                'url' => $this->urlCalendario($parametros),
                'asistencia_id' => $asistencia->id,
                'justificacion_id' => $justificacion?->id,
                'fecha' => $this->fechaCalendario($datos, $asistencia),
            ];
        }

        return $destinoSeguro;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function primeraAsistencia(array $datos): ?Asistencia
    {
        $ids = collect($datos['asistencia_ids'] ?? [])
            ->filter(fn (mixed $id): bool => is_int($id) || (is_string($id) && ctype_digit($id)))
            ->values();

        foreach ($ids as $id) {
            $asistencia = Asistencia::find($id);

            if ($asistencia !== null) {
                return $asistencia;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, int|string>
     */
    private function parametrosCalendario(?Asistencia $asistencia, array $datos): array
    {
        $parametros = [];
        $fecha = $this->fechaCalendario($datos, $asistencia);

        if ($fecha !== null) {
            $parametros['fecha'] = $fecha;
        }

        if ($asistencia !== null) {
            $parametros['asistencia'] = $asistencia->id;
        }

        return $parametros;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function fechaCalendario(array $datos, ?Asistencia $asistencia): ?string
    {
        foreach (['fecha_calendario', 'asistencia_fecha'] as $clave) {
            $fecha = $datos[$clave] ?? null;

            if (is_string($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1) {
                return $fecha;
            }
        }

        if ($asistencia?->fecha !== null) {
            return (string) $asistencia->fecha;
        }

        $fecha = $datos['fecha'] ?? null;

        if (! is_string($fecha)) {
            return null;
        }

        try {
            $fechaCalendario = Carbon::createFromFormat('!d/m/Y', $fecha);

            return $fechaCalendario && $fechaCalendario->format('d/m/Y') === $fecha
                ? $fechaCalendario->toDateString()
                : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, int|string>  $parametros
     */
    private function urlCalendario(array $parametros): string
    {
        if (! Route::has('admin.calendar.index')) {
            return $this->urlGeneral();
        }

        return route('admin.calendar.index', $parametros);
    }

    private function urlGeneral(): string
    {
        return Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    }
}
