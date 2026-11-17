<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Roles y permisos del sistema (equivalente a PermissionSeeder + RoleSeeder,
        // pero sin el usuario hardcodeado User::find(1)).
        $this->seedRolesYPermisos();

        // Función SQL TIME_TO_SEC para que las consultas escritas para MySQL
        // (agregaciones de tiempo) funcionen también en la base sqlite de pruebas.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction(
                'TIME_TO_SEC',
                function ($time) {
                    if ($time === null) {
                        return null;
                    }

                    $partes = array_map('intval', explode(':', (string) $time));

                    return ($partes[0] ?? 0) * 3600 + ($partes[1] ?? 0) * 60 + ($partes[2] ?? 0);
                }
            );
        }
    }

    /**
     * Reproduce el seed de roles y permisos del entorno real.
     */
    private function seedRolesYPermisos(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
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

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(array_diff($permisos, ['mark.attendance']));

        $practicante = Role::firstOrCreate(['name' => 'practicante']);
        $practicante->syncPermissions([
            'read.calendar',
            'mark.attendance',
            'read.justificate',
            'read.dashboard',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
