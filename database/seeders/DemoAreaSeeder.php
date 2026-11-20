<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoAreaSeeder extends Seeder
{
    /** Catálogo vigente de áreas de demostración. */
    public const NOMBRES = [
        'Diseño gráfico',
        'Desarrollo web',
        'Desarrollo móvil',
        'Diseño web',
        'Base de datos',
    ];

    /** Equivalencia de las áreas históricas con las vigentes. */
    private const EQUIVALENCIAS = [
        'Sistemas' => 'Desarrollo web',
        'Diseño' => 'Diseño gráfico',
        'Administración' => 'Base de datos',
    ];

    private const AREA_POR_DEFECTO = 'Desarrollo web';

    /**
     * @return array<int, string>
     */
    public static function nombres(): array
    {
        return self::NOMBRES;
    }

    public function run(): void
    {
        $areas = collect(self::NOMBRES)
            ->mapWithKeys(fn (string $nombre): array => [
                $nombre => Area::updateOrCreate(['nombre' => $nombre]),
            ]);

        // El FK usuarios.area_id es ON DELETE CASCADE: primero se reasigna a
        // los usuarios para que ninguna eliminación posterior los borre.
        $this->reasignarUsuarios($areas);
        $this->eliminarAreasObsoletas($areas);
    }

    /**
     * @param  Collection<string, Area>  $areas
     */
    private function reasignarUsuarios(Collection $areas): void
    {
        User::query()
            ->whereNotNull('area_id')
            ->whereNotIn('area_id', $areas->pluck('id')->all())
            ->with('area:id,nombre')
            ->get(['id', 'area_id'])
            ->each(function (User $usuario) use ($areas): void {
                $nombreAnterior = $usuario->area?->nombre;
                $nombreDestino = self::EQUIVALENCIAS[$nombreAnterior] ?? self::AREA_POR_DEFECTO;

                $usuario->area_id = $areas->get($nombreDestino)->id;
                $usuario->save();
            });
    }

    /**
     * Elimina únicamente áreas sin usuarios: ninguna otra tabla las referencia.
     *
     * @param  Collection<string, Area>  $areas
     */
    private function eliminarAreasObsoletas(Collection $areas): void
    {
        Area::query()
            ->whereNotIn('id', $areas->pluck('id')->all())
            ->doesntHave('users')
            ->delete();
    }
}
