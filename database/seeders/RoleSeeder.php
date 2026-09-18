<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin= Role::create([
            'name' => 'admin'
        ]);

        $admin->syncPermissions([
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
        ]);

        $user = User::find(1);
        $user->assignRole('admin');

        $practicing = Role::create([
            'name' => 'practicing'
        ]);

        $practicing->syncPermissions([
            'read.calendar',
            'mark.attendance'
        ]);





    }
}
