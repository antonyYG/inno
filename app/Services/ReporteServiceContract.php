<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ReporteServiceContract
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function paginar(array $filtros, int $porPagina = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filtros
     * @return Collection<int, object>
     */
    public function obtenerTodos(array $filtros): Collection;

    /**
     * @param  array<string, mixed>  $filtros
     * @return array<string, int>
     */
    public function resumen(array $filtros): array;
}
