<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Filtros y catálogos compartidos por todos los reportes.
 *
 * No resuelve consultas de negocio: solo normaliza criterios y aplica
 * restricciones reutilizables sobre usuarios, fechas y catálogos reales.
 */
class ReporteFiltroService
{
    /**
     * @param  array<string, mixed>  $entrada
     * @return array<string, mixed>
     */
    public function normalizar(array $entrada): array
    {
        return [
            'fechaDesde' => $entrada['fechaDesde'] ?? null,
            'fechaHasta' => $entrada['fechaHasta'] ?? null,
            'practicante' => $entrada['practicante'] ?? null,
            'administrador' => $entrada['administrador'] ?? null,
            'estado' => $entrada['estado'] ?? $entrada['estadoAsistencia'] ?? $entrada['accion'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function aplicarFechas(Builder $consulta, array $filtros, string $columna): void
    {
        $consulta
            ->when($filtros['fechaDesde'] ?? null, function (Builder $query, string $fecha) use ($columna): void {
                $query->whereDate($columna, '>=', $fecha);
            })
            ->when($filtros['fechaHasta'] ?? null, function (Builder $query, string $fecha) use ($columna): void {
                $query->whereDate($columna, '<=', $fecha);
            });
    }

    /**
     * Aplica el rol real de Spatie sobre una consulta de usuarios.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function aplicarRol(Builder $consulta, array $filtros): void
    {
        $rol = $filtros['rol'] ?? null;

        if ($rol === null) {
            return;
        }

        $consulta->whereHas(
            'roles',
            fn (Builder $roles): mixed => $roles->where('name', $rol)
        );
    }

    /**
     * Aplica el practicante seleccionado sobre la relación indicada.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function aplicarUsuario(Builder $consulta, array $filtros, string $relacion): void
    {
        $practicante = $filtros['practicante'] ?? null;

        if ($practicante === null) {
            return;
        }

        $consulta->whereHas(
            $relacion,
            fn (Builder $usuario): mixed => $usuario->whereKey($practicante)
        );
    }

    /**
     * @return array{practicantes: array<int|string, string>, administradores: array<int|string, string>}
     */
    public function catalogos(): array
    {
        $usuarios = User::query()
            ->with('roles')
            ->select('id', 'nombre', 'apellidos')
            ->where('estado', 1)
            ->orderBy('apellidos')
            ->orderBy('nombre')
            ->get();

        return [
            'practicantes' => $this->usuariosPorRol($usuarios, 'practicante', 'Todos los practicantes'),
            'administradores' => $this->usuariosPorRol($usuarios, 'admin', 'Todos los administradores'),
        ];
    }

    /**
     * @param  Collection<int, User>  $usuarios
     * @return array<int|string, string>
     */
    private function usuariosPorRol(Collection $usuarios, string $rol, string $etiqueta): array
    {
        return $usuarios
            ->filter(fn (User $usuario): bool => $usuario->hasRole($rol))
            ->mapWithKeys(fn (User $usuario): array => [$usuario->id => $this->nombreCompleto($usuario)])
            ->prepend($etiqueta, '')
            ->all();
    }

    private function nombreCompleto(User $usuario): string
    {
        return trim(($usuario->nombre ?? '').' '.($usuario->apellidos ?? '')) ?: 'No identificado';
    }
}
