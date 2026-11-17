<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use WikiGods\ImageGenerator\Facades\ImageGenerator;

class AsistenciaJustificacionSeeder extends Seeder
{
    public function run(): void
    {
        $directorio = storage_path('app/public/evidencias');

        File::ensureDirectoryExists($directorio);
        $practicantes = User::role('practicante')
            ->where('estado', true)
            ->get();

        if ($practicantes->isEmpty()) {
            $this->command->warn('No existen practicantes activos para generar asistencias.');

            return;
        }

        $hoy = Carbon::now('America/Lima')->startOfDay();

        foreach ($practicantes as $practicante) {

            // Generar asistencias de los últimos 15 días laborables
            $diasGenerados = 0;
            $fecha = $hoy->copy()->subDay();

            while ($diasGenerados < 15) {

                // Excluir sábados y domingos
                if ($fecha->isWeekday()) {

                    $estado = fake()->randomElement([
                        'asistio',
                        'asistio',
                        'asistio',
                        'tardanza',
                        'falto',
                        'pendiente',
                    ]);

                    $horaInicio = null;
                    $horaFin = null;
                    $tiempoTrabajado = null;

                    if ($estado === 'asistio') {
                        $horaInicio = '08:00:00';
                        $horaFin = '13:00:00';
                        $tiempoTrabajado = '05:00:00';
                    }

                    if ($estado === 'tardanza') {
                        $horaInicio = '08:05:00';
                        $horaFin = '13:00:00';
                        $tiempoTrabajado = '04:55:00';
                    }

                    $asistencia = Asistencia::updateOrCreate(
                        [
                            'usuario_id' => $practicante->id,
                            'fecha' => $fecha->toDateString(),
                        ],
                        [
                            'hora_inicio' => $horaInicio,
                            'hora_fin' => $horaFin,
                            'tiempo_trabajado' => $tiempoTrabajado,
                            'tipo' => $estado,
                        ]
                    );

                    // Generar evidencia para asistencias y tardanzas
                    if (in_array($estado, ['asistio', 'tardanza'])) {

                        $imagen = ImageGenerator::image(
                            dir: $directorio,
                            text: 'Evidencia de practica - '.$practicante->nombre,
                            width: 800,
                            height: 600,
                            fullPath: true,
                            format: 'png',
                            category: 'office'
                        );

                        Justificacion::firstOrCreate(
                            [
                                'asistencia_id' => $asistencia->id,
                                'tipo' => 'evidencia',
                            ],
                            [
                                'descripcion' => 'Evidencia de actividades realizadas durante la jornada de prácticas.',
                                'archivo_path' => 'evidencias/'.basename($imagen),
                                'observacion' => null,
                                'estado' => fake()->randomElement([
                                    'pendiente',
                                    'aprobada',
                                    'rechazada',
                                ]),
                                'fecha' => $fecha->toDateString(),
                            ]
                        );
                    }

                    // Generar justificación para faltas y tardanzas
                    if (in_array($estado, ['falto', 'tardanza'])) {

                        $justificacionExistente = Justificacion::where(
                            'asistencia_id',
                            $asistencia->id
                        )
                            ->where('tipo', 'justificacion')
                            ->exists();

                        if (! $justificacionExistente) {

                            $imagen = ImageGenerator::image(
                                dir: $directorio,
                                text: 'Documento de justificacion - '.$practicante->nombre,
                                width: 800,
                                height: 600,
                                fullPath: true,
                                format: 'png',
                                category: 'office'
                            );

                            Justificacion::create([
                                'asistencia_id' => $asistencia->id,
                                'tipo' => 'justificacion',
                                'descripcion' => $estado === 'falto'
                                    ? 'Solicito justificar mi inasistencia por motivos personales.'
                                    : 'Solicito justificar mi tardanza por inconvenientes en el traslado.',
                                'archivo_path' => 'evidencias/'.basename($imagen),
                                'observacion' => null,
                                'estado' => 'pendiente',
                                'fecha' => $fecha->toDateString(),
                            ]);
                        }
                    }

                    $diasGenerados++;
                }

                $fecha->subDay();
            }
        }

        $this->command->info('Asistencias y justificaciones generadas correctamente.');
    }
}
