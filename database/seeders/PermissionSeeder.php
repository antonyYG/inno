<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Permisos vigentes del sistema.
     *
     * @return array<int, string>
     */
    public static function permisos(): array
    {
        return [
            'create.users',
            'edit.users',
            'delete.users',
            'read.users',
            'read.calendar',
            'create.areas',
            'edit.areas',
            'delete.areas',
            'read.areas',
            'read.justificate',
            'mark.attendance',
            'explain.absence',
            'read.dashboard',
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::permisos() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
