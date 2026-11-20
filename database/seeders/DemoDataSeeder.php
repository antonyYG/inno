<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder maestro de datos ficticios de PractiControl.
 *
 * Ejecutable varias veces: ninguno de los seeders hijos elimina registros
 * ni crea duplicados.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoAreaSeeder::class,
            DemoUserSeeder::class,
            DemoAsistenciaSeeder::class,
            DemoBitacoraSeeder::class,
        ]);
    }
}
