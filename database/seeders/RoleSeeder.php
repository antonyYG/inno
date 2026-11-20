<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Permisos asignados a cada rol demo.
     *
     * @return array<string, array<int, string>>
     */
    public static function permisosPorRol(): array
    {
        return [
            'admin' => array_values(array_diff(
                PermissionSeeder::permisos(),
                ['mark.attendance']
            )),
            'practicante' => [
                'read.calendar',
                'mark.attendance',
                'read.justificate',
                'read.dashboard',
            ],
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::permisosPorRol() as $nombre => $permisos) {
            $role = Role::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permisos);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
