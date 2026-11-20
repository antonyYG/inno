<?php

namespace App\Services;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReporteAsistenciaService implements ReporteServiceContract
{
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
            ->latest('fecha')
            ->latest('id')
            ->paginate($porPagina)
            ->withQueryString();

        $paginador->setCollection(
            $paginador->getCollection()->map(fn (Asistencia $asistencia): object => $this->normalizar($asistencia))
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
            ->latest('fecha')
            ->latest('id')
            ->get()
            ->map(fn (Asistencia $asistencia): object => $this->normalizar($asistencia));
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, int>
     */
    public function resumen(array $filtros): array
    {
        $filtros = $this->filtroService->normalizar($filtros);

        $conteos = $this->consulta($filtros, false)
            ->selectRaw('tipo as estado_tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->pluck('total', 'estado_tipo');

        return collect(array_column(EstadoAsistenciaEnum::cases(), 'value'))
            ->mapWithKeys(fn (string $estado): array => [$estado => (int) ($conteos[$estado] ?? 0)])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros, bool $conRelaciones = true): Builder
    {
        $consulta = Asistencia::query();

        if ($conRelaciones) {
            $consulta->with(['usuario.area', 'usuario.roles']);
        }

        $this->filtroService->aplicarFechas($consulta, $filtros, 'fecha');
        $this->filtroService->aplicarUsuario($consulta, $filtros, 'usuario');

        return $consulta->when($filtros['estado'] ?? null, function (Builder $query, string $estado): void {
            $query->where('tipo', $estado);
        });
    }

    private function normalizar(Asistencia $asistencia): object
    {
        $usuario = $asistencia->usuario;

        return (object) [
            'id' => $asistencia->id,
            'practicante_id' => $usuario?->id,
            'practicante_nombre' => $usuario?->nombre,
            'practicante_apellidos' => $usuario?->apellidos,
            'practicante' => trim(($usuario?->nombre ?? '').' '.($usuario?->apellidos ?? '')) ?: 'Sin practicante',
            'area' => $usuario?->area?->nombre ?: 'No disponible',
            'fecha' => $asistencia->fecha,
            'hora_inicio' => $this->formatearHora($asistencia->hora_inicio),
            'hora_fin' => $this->formatearHora($asistencia->hora_fin),
            'tiempo_trabajado' => $this->formatearTiempo($asistencia->tiempo_trabajado),
            'estado' => $this->estadoValor($asistencia->tipo),
        ];
    }

    private function estadoValor(mixed $estado): string
    {
        return $estado instanceof \BackedEnum
            ? $estado->value
            : (string) $estado;
    }

    private function formatearHora(mixed $valor): string
    {
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('H:i:s');
        }

        return $valor ? (string) $valor : '-';
    }

    private function formatearTiempo(mixed $valor): string
    {
        if ($valor instanceof DateInterval) {
            return $valor->format('%H:%I:%S');
        }

        return $valor ? (string) $valor : '-';
    }
}
