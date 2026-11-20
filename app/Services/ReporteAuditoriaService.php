<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Consulta el historial real de activity_log para el reporte de auditoría.
 *
 * Incluye decisiones nuevas, auditorías legacy de justificaciones y evidencias,
 * y actividades diarias. No migra ni fabrica registros.
 */
class ReporteAuditoriaService implements ReporteServiceContract
{
    private const NO_DISPONIBLE = 'No disponible';

    /**
     * @var array<string, string>
     */
    private const ACCIONES_POR_EVENTO = [
        'accepted' => 'aceptada',
        'justificacion_aprobada' => 'aceptada',
        'evidencia_validada' => 'aceptada',
        'rejected' => 'rechazada',
        'justificacion_rechazada' => 'rechazada',
        'evidencia_rechazada' => 'rechazada',
        'actividad_diaria' => 'actividad',
    ];

    public function __construct(
        protected ReporteFiltroService $filtroService
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function paginar(array $filtros, int $porPagina = 15): LengthAwarePaginator
    {
        $filtros = $this->filtroService->normalizar($filtros);

        $paginador = $this->consulta($filtros)
            ->latest('activity_log.created_at')
            ->latest('activity_log.id')
            ->paginate($porPagina)
            ->withQueryString();

        $paginador->setCollection(
            $paginador->getCollection()->map(fn (Activity $actividad): object => $this->normalizar($actividad))
        );

        return $paginador;
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return Collection<int, object>
     */
    public function obtenerTodos(array $filtros): Collection
    {
        $filtros = $this->filtroService->normalizar($filtros);

        return $this->consulta($filtros)
            ->latest('activity_log.created_at')
            ->latest('activity_log.id')
            ->get()
            ->map(fn (Activity $actividad): object => $this->normalizar($actividad));
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, int>
     */
    public function resumen(array $filtros): array
    {
        $filtros = $this->filtroService->normalizar($filtros);

        $conteos = $this->consulta($filtros, false)
            ->selectRaw('event as evento, COUNT(*) as total')
            ->groupBy('event')
            ->pluck('total', 'evento');

        $resumen = [
            'aceptada' => 0,
            'rechazada' => 0,
            'actividad' => 0,
        ];

        foreach ($conteos as $evento => $total) {
            $accion = self::ACCIONES_POR_EVENTO[$evento] ?? null;

            if ($accion !== null && array_key_exists($accion, $resumen)) {
                $resumen[$accion] += (int) $total;
            }
        }

        return $resumen;
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros, bool $conRelaciones = true): Builder
    {
        $consulta = Activity::query();

        if ($conRelaciones) {
            $consulta->with([
                'causer' => function (MorphTo $relacion): void {
                    $relacion->morphWith([
                        User::class => ['area', 'roles'],
                    ]);
                },
                'subject' => function (MorphTo $relacion): void {
                    $relacion->morphWith([
                        Justificacion::class => ['asistencia.usuario.area', 'asistencia.usuario.roles'],
                        Asistencia::class => ['usuario.area', 'usuario.roles', 'justificaciones'],
                    ]);
                },
            ]);
        }

        $this->filtroService->aplicarFechas($consulta, $filtros, 'activity_log.created_at');
        $this->aplicarPracticante($consulta, $filtros);
        $this->aplicarAdministrador($consulta, $filtros);
        $this->aplicarAccion($consulta, $filtros);

        return $consulta;
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarPracticante(Builder $consulta, array $filtros): void
    {
        $practicante = $filtros['practicante'] ?? null;

        if ($practicante === null) {
            return;
        }

        $this->aplicarSobrePracticante($consulta, [
            'practicante' => $practicante,
            'rol' => 'practicante',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarAdministrador(Builder $consulta, array $filtros): void
    {
        $administrador = $filtros['administrador'] ?? null;

        if ($administrador === null) {
            return;
        }

        $this->aplicarSobreCauser($consulta, [
            'administrador' => $administrador,
            'rol' => 'admin',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarSobreCauser(Builder $consulta, array $filtros): void
    {
        $consulta->whereHasMorph(
            'causer',
            [User::class],
            function (Builder $causer) use ($filtros): void {
                $this->filtroService->aplicarRol($causer, $filtros);

                if (($filtros['administrador'] ?? null) !== null) {
                    $causer->whereKey($filtros['administrador']);
                }
            }
        );
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarSobrePracticante(Builder $consulta, array $filtros): void
    {
        $consulta->whereHasMorph(
            'subject',
            [Justificacion::class, Asistencia::class],
            function (Builder $subject, string $tipo) use ($filtros): void {
                $relacion = $tipo === Asistencia::class
                    ? 'usuario'
                    : 'asistencia.usuario';

                $subject->whereHas($relacion, function (Builder $usuario) use ($filtros): void {
                    $this->filtroService->aplicarRol($usuario, $filtros);

                    if (($filtros['practicante'] ?? null) !== null) {
                        $usuario->whereKey($filtros['practicante']);
                    }
                });
            }
        );
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarAccion(Builder $consulta, array $filtros): void
    {
        $accion = $filtros['estado'] ?? null;

        if ($accion === null) {
            return;
        }

        $eventos = array_keys(array_filter(
            self::ACCIONES_POR_EVENTO,
            fn (string $valor): bool => $valor === $accion
        ));

        $consulta->whereIn('event', $eventos === [] ? ['__sin_evento__'] : $eventos);
    }

    private function normalizar(Activity $actividad): object
    {
        $propiedades = $actividad->properties;
        $cambios = $actividad->attribute_changes;

        $justificacion = null;
        $asistencia = null;

        if ($actividad->subject instanceof Justificacion) {
            $justificacion = $actividad->subject;
            $asistencia = $justificacion->asistencia;
        } elseif ($actividad->subject instanceof Asistencia) {
            $asistencia = $actividad->subject;
            $justificacionId = data_get($propiedades, 'justificacion_id');

            $justificacion = $asistencia->relationLoaded('justificaciones')
                ? $asistencia->justificaciones->firstWhere('id', $justificacionId)
                : null;
        }

        $practicante = $asistencia?->usuario;
        $administrador = $actividad->causer instanceof User
            ? $actividad->causer
            : null;

        $estadoAnterior = data_get($cambios, 'datos_anteriores.estado')
            ?? data_get($propiedades, 'datos_anteriores.estado')
            ?? data_get($propiedades, 'estado_anterior');
        $estadoNuevo = data_get($cambios, 'datos_nuevos.estado')
            ?? data_get($propiedades, 'datos_nuevos.estado')
            ?? data_get($propiedades, 'estado_nuevo');

        $detalle = $justificacion?->descripcion ?: self::NO_DISPONIBLE;
        $observacion = data_get($propiedades, 'observacion') ?: self::NO_DISPONIBLE;

        return (object) [
            'id' => $actividad->id,
            'fecha_revision' => $actividad->created_at?->format('d/m/Y H:i') ?? self::NO_DISPONIBLE,
            'log_name' => $actividad->log_name,
            'event' => $actividad->event,
            'justificacion_id' => $justificacion?->id ?? data_get($propiedades, 'justificacion_id'),
            'asistencia_id' => data_get($propiedades, 'asistencia_id') ?? $asistencia?->id,
            'practicante_id' => $practicante?->id,
            'practicante_nombre' => $practicante?->nombre,
            'practicante_apellidos' => $practicante?->apellidos,
            'practicante' => $this->nombreCompleto($practicante?->nombre, $practicante?->apellidos),
            'area' => $practicante?->area?->nombre ?: self::NO_DISPONIBLE,
            'justificacion' => $detalle,
            'detalle' => $detalle,
            'fecha_asistencia' => $asistencia?->fecha ?: self::NO_DISPONIBLE,
            'administrador_id' => $administrador?->id,
            'administrador_nombre' => $administrador?->nombre,
            'administrador_apellidos' => $administrador?->apellidos,
            'administrador' => $this->nombreCompleto($administrador?->nombre, $administrador?->apellidos),
            'accion' => self::ACCIONES_POR_EVENTO[$actividad->event] ?? self::NO_DISPONIBLE,
            'observacion' => $observacion,
            'estado_anterior' => $estadoAnterior ?? self::NO_DISPONIBLE,
            'estado_nuevo' => $estadoNuevo ?? self::NO_DISPONIBLE,
        ];
    }

    private function nombreCompleto(?string $nombre, ?string $apellidos): string
    {
        $completo = trim(($nombre ?? '').' '.($apellidos ?? ''));

        return $completo !== '' ? $completo : self::NO_DISPONIBLE;
    }
}
