<?php

namespace App\Services;

use App\Models\Justificacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ReporteEvidenciaService implements ReporteServiceContract
{
    private const NO_DISPONIBLE = 'No disponible';

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
            $paginador->getCollection()->map(fn (Justificacion $evidencia): object => $this->normalizar($evidencia))
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
            ->map(fn (Justificacion $evidencia): object => $this->normalizar($evidencia));
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, int>
     */
    public function resumen(array $filtros): array
    {
        $filtros = $this->filtroService->normalizar($filtros);

        $conteos = $this->consulta($filtros, false)
            ->selectRaw('estado as estado_valor, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado_valor');

        return collect(['pendiente', 'aprobada', 'rechazada'])
            ->mapWithKeys(fn (string $estado): array => [$estado => (int) ($conteos[$estado] ?? 0)])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(array $filtros, bool $conRelaciones = true): Builder
    {
        $consulta = Justificacion::query()->where('tipo', 'evidencia');

        if ($conRelaciones) {
            $consulta->with(['asistencia.usuario.area', 'asistencia.usuario.roles']);
        }

        $this->filtroService->aplicarFechas($consulta, $filtros, 'fecha');
        $this->filtroService->aplicarUsuario($consulta, $filtros, 'asistencia.usuario');

        return $consulta->when($filtros['estado'] ?? null, function (Builder $query, string $estado): void {
            $query->where('estado', $estado);
        });
    }

    private function normalizar(Justificacion $evidencia): object
    {
        $usuario = $evidencia->asistencia?->usuario;
        $archivo = $evidencia->archivo_path;

        return (object) [
            'id' => $evidencia->id,
            'practicante_id' => $usuario?->id,
            'practicante_nombre' => $usuario?->nombre,
            'practicante_apellidos' => $usuario?->apellidos,
            'practicante' => trim(($usuario?->nombre ?? '').' '.($usuario?->apellidos ?? '')) ?: 'Sin practicante',
            'area' => $usuario?->area?->nombre ?: self::NO_DISPONIBLE,
            'fecha' => $evidencia->fecha,
            'descripcion' => $evidencia->descripcion ?: self::NO_DISPONIBLE,
            'estado' => $evidencia->estado ?: self::NO_DISPONIBLE,
            'observacion' => $evidencia->observacion ?: self::NO_DISPONIBLE,
            'archivo' => $archivo ? Storage::disk('public')->url($archivo) : null,
            'nombre_archivo' => $archivo ? basename($archivo) : null,
        ];
    }
}
