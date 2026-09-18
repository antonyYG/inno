<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        Area::factory(20)->create();
        User::factory()->create([
            'name' => 'pepe',
            'email' => 'pepe@gmail.com',
            'password' => bcrypt('12345678'),
        ]);

        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class
        ]);


    }
}
