<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'create.users',
            'edit.users',
            'delete.users',
            'read.users',
            'read.calendar',
            'create.areas',
            'edit.areas',
            'delete.areas',
            'mark.attendance',
            'explain.absence'
        ];

        foreach ($permissions as $permission) {

            Permission::create([
                'name' => $permission
            ]);

        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

    }
}
