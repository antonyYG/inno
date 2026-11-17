<?php

namespace Database\Seeders;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use WikiGods\ImageGenerator\Facades\ImageGenerator;

/**
 * Seeder de datos ficticios para demostración (noviembre 2026).
 *
 * - Solo crea registros NUEVOS: no modifica usuarios, asistencias ni
 *   justificaciones existentes (pepe y juan quedan intactos).
 * - Idempotente: re-ejecutarlo no duplica practicantes, asistencias,
 *   evidencias ni justificaciones (firstOrCreate en todas las entidades).
 * - Respeta las reglas reales del sistema:
 *   · asistio   -> tiempo contabilizado desde las 08:00 (hora_inicio 08:00:00)
 *   · tardanza  -> contabiliza desde el ingreso real, hasta 13:00 como máximo
 *   · falto     -> sin horas
 *   · justificado (justificación de falta aprobada) -> sin horas
 *   · evidencia rechazada -> la asistencia queda como falto (rechazarAsistencia)
 *   · justificación de tardanza aprobada -> la tardanza se conserva
 */
class PractiControlDemoSeeder extends Seeder
{
    /** Días laborables del 1 al 16 de noviembre de 2026. */
    private const FECHAS = [
        '2026-11-02', '2026-11-03', '2026-11-04', '2026-11-05', '2026-11-06',
        '2026-11-09', '2026-11-10', '2026-11-11', '2026-11-12', '2026-11-13',
        '2026-11-16',
    ];

    public function run(): void
    {
        $this->command->info('Creando practicantes ficticios...');

        foreach ($this->practicantes() as $datos) {
            $atributos = collect($datos)->except('asistencias')->all();

            $usuario = User::firstOrCreate(
                ['email' => $atributos['email']],
                $atributos
            );

            if (! $usuario->hasRole('practicante')) {
                $usuario->assignRole('practicante');
            }
        }

        foreach ($this->practicantes() as $indice => $datos) {
            $jornadas = $datos['asistencias'];
            unset($datos['asistencias']);

            $usuario = User::where('email', $datos['email'])->first();

            $this->command->info("Generando asistencias de {$usuario->nombre} {$usuario->apellidos}...");

            foreach ($jornadas as $fecha => $jornada) {
                $this->generarJornada($usuario, $fecha, $jornada);
            }
        }

        $this->command->info('Datos ficticios de PractiControl generados correctamente.');
    }

    /**
     * Definición de los cinco practicantes ficticios y sus jornadas.
     */
    private function practicantes(): array
    {
        return [
            // 1) Puntual: todos los días asistio con jornada completa.
            [
                'nombre' => 'Lucía',
                'apellidos' => 'Fernández Ramos',
                'email' => 'lucia.fernandez@demo.test',
                'password' => Hash::make('demo1234'),
                'area_id' => 2,
                'discord_id' => '200111222333444001',
                'estado' => true,
                'fecha_inicio' => '2026-11-02',
                'fecha_fin' => '2026-12-18',
                'horas_requeridas' => 360,
                'asistencias' => [
                    '2026-11-02' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-03' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-04' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-05' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-06' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-09' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-10' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-11' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-12' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    // Día sin evidencia: comportamiento natural (olvidó subirla).
                    '2026-11-13' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00'],
                    '2026-11-16' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'pendiente'],
                ],
            ],

            // 2) Con tardanzas frecuentes, reconexiones y justificaciones de tardanza.
            [
                'nombre' => 'Mario',
                'apellidos' => 'Castillo Vega',
                'email' => 'mario.castillo@demo.test',
                'password' => Hash::make('demo1234'),
                'area_id' => 3,
                'discord_id' => '200111222333444002',
                'estado' => true,
                'fecha_inicio' => '2026-11-02',
                'fecha_fin' => '2026-12-18',
                'horas_requeridas' => 360,
                'asistencias' => [
                    '2026-11-02' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-03' => ['tipo' => 'tardanza', 'inicio' => '08:25:00', 'fin' => '13:00:00', 'tiempo' => '04:35:00', 'evidencia' => 'aprobada'],
                    '2026-11-04' => ['tipo' => 'tardanza', 'inicio' => '09:15:00', 'fin' => '13:00:00', 'tiempo' => '03:45:00', 'justificacion' => 'pendiente'],
                    // Desconexión y reconexión: 08:40-10:30 + 11:00-13:00 = 03:50.
                    '2026-11-05' => ['tipo' => 'tardanza', 'inicio' => '08:40:00', 'fin' => '13:00:00', 'tiempo' => '03:50:00', 'evidencia' => 'pendiente'],
                    '2026-11-06' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    // Tardanza justificada y APROBADA: la tardanza se conserva.
                    '2026-11-09' => ['tipo' => 'tardanza', 'inicio' => '08:10:00', 'fin' => '13:00:00', 'tiempo' => '04:50:00', 'justificacion' => 'aprobada'],
                    '2026-11-10' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-11' => ['tipo' => 'tardanza', 'inicio' => '08:55:00', 'fin' => '12:30:00', 'tiempo' => '03:35:00'],
                    '2026-11-12' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    // Tardanza justificada y RECHAZADA: la tardanza se conserva.
                    '2026-11-13' => ['tipo' => 'tardanza', 'inicio' => '08:30:00', 'fin' => '13:00:00', 'tiempo' => '04:30:00', 'justificacion' => 'rechazada'],
                    '2026-11-16' => ['tipo' => 'tardanza', 'inicio' => '08:20:00', 'fin' => '13:00:00', 'tiempo' => '04:40:00'],
                ],
            ],

            // 3) Irregular: faltas con justificaciones y jornadas variadas.
            [
                'nombre' => 'Sofía',
                'apellidos' => 'Mendoza Torres',
                'email' => 'sofia.mendoza@demo.test',
                'password' => Hash::make('demo1234'),
                'area_id' => 4,
                'discord_id' => '200111222333444003',
                'estado' => true,
                'fecha_inicio' => '2026-11-02',
                'fecha_fin' => '2026-12-18',
                'horas_requeridas' => 360,
                'asistencias' => [
                    '2026-11-02' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-03' => ['tipo' => 'tardanza', 'inicio' => '08:35:00', 'fin' => '13:00:00', 'tiempo' => '04:25:00', 'evidencia' => 'pendiente'],
                    // Falta con justificación PENDIENTE.
                    '2026-11-04' => ['tipo' => 'falto', 'justificacion' => 'pendiente'],
                    '2026-11-05' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    // Salió antes del cierre.
                    '2026-11-06' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '12:15:00', 'tiempo' => '04:15:00', 'evidencia' => 'aprobada'],
                    // Falta con justificación RECHAZADA: la falta se mantiene.
                    '2026-11-09' => ['tipo' => 'falto', 'justificacion' => 'rechazada'],
                    // Evidencia rechazada: la asistencia queda como falta (rechazarAsistencia).
                    '2026-11-10' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'rechazada'],
                    '2026-11-11' => ['tipo' => 'tardanza', 'inicio' => '08:20:00', 'fin' => '13:00:00', 'tiempo' => '04:40:00'],
                    '2026-11-12' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'pendiente'],
                    '2026-11-13' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-16' => ['tipo' => 'tardanza', 'inicio' => '08:45:00', 'fin' => '13:00:00', 'tiempo' => '04:15:00', 'evidencia' => 'pendiente'],
                ],
            ],

            // 4) Muchas evidencias pendientes de revisión y una falta justificada.
            [
                'nombre' => 'Daniel',
                'apellidos' => 'Quispe Rojas',
                'email' => 'daniel.quispe@demo.test',
                'password' => Hash::make('demo1234'),
                'area_id' => 5,
                'discord_id' => '200111222333444004',
                'estado' => true,
                'fecha_inicio' => '2026-11-02',
                'fecha_fin' => '2026-12-18',
                'horas_requeridas' => 360,
                'asistencias' => [
                    '2026-11-02' => ['tipo' => 'tardanza', 'inicio' => '08:30:00', 'fin' => '13:00:00', 'tiempo' => '04:30:00', 'evidencia' => 'pendiente'],
                    '2026-11-03' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'pendiente'],
                    // Falta sin justificar.
                    '2026-11-04' => ['tipo' => 'falto'],
                    '2026-11-05' => ['tipo' => 'tardanza', 'inicio' => '08:50:00', 'fin' => '13:00:00', 'tiempo' => '04:10:00', 'evidencia' => 'pendiente'],
                    // Desconexión temprana.
                    '2026-11-06' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '11:30:00', 'tiempo' => '03:30:00', 'evidencia' => 'aprobada'],
                    // Evidencia rechazada: queda como falta.
                    '2026-11-09' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'rechazada'],
                    '2026-11-10' => ['tipo' => 'tardanza', 'inicio' => '09:00:00', 'fin' => '13:00:00', 'tiempo' => '04:00:00', 'evidencia' => 'pendiente'],
                    // Falta con justificación APROBADA: el estado pasa a justificado.
                    '2026-11-11' => ['tipo' => 'justificado', 'justificacion' => 'aprobada'],
                    '2026-11-12' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-13' => ['tipo' => 'tardanza', 'inicio' => '08:15:00', 'fin' => '13:00:00', 'tiempo' => '04:45:00'],
                    '2026-11-16' => ['tipo' => 'tardanza', 'inicio' => '08:35:00', 'fin' => '13:00:00', 'tiempo' => '04:25:00', 'evidencia' => 'pendiente'],
                ],
            ],

            // 5) Periodo vencido: sus prácticas terminaron el 13/11; no registra el 16.
            [
                'nombre' => 'Valeria',
                'apellidos' => 'Huamán Soto',
                'email' => 'valeria.huaman@demo.test',
                'password' => Hash::make('demo1234'),
                'area_id' => 7,
                'discord_id' => '200111222333444005',
                'estado' => true,
                'fecha_inicio' => '2026-11-02',
                'fecha_fin' => '2026-11-13',
                'horas_requeridas' => 360,
                'asistencias' => [
                    '2026-11-02' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    // Falta sin justificar.
                    '2026-11-03' => ['tipo' => 'falto'],
                    '2026-11-04' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    // Salió temprano.
                    '2026-11-05' => ['tipo' => 'tardanza', 'inicio' => '08:30:00', 'fin' => '11:00:00', 'tiempo' => '02:30:00', 'evidencia' => 'pendiente'],
                    '2026-11-06' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-09' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00'],
                    '2026-11-10' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
                    '2026-11-11' => ['tipo' => 'tardanza', 'inicio' => '08:20:00', 'fin' => '13:00:00', 'tiempo' => '04:40:00'],
                    // Falta con justificación PENDIENTE.
                    '2026-11-12' => ['tipo' => 'falto', 'justificacion' => 'pendiente'],
                    // Último día de su periodo.
                    '2026-11-13' => ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'pendiente'],
                    // 16/11: sin registro. Su periodo finalizó el 13/11; el cierre
                    // automático no le generaría falta (fuera del periodo).
                ],
            ],
        ];
    }

    /**
     * Crea la asistencia del día y sus evidencias/justificaciones.
     */
    private function generarJornada(User $usuario, string $fecha, array $jornada): void
    {
        $tipo = EstadoAsistenciaEnum::from($jornada['tipo']);

        // Rechazo de evidencia: el sistema convierte la asistencia en falta.
        if (($jornada['evidencia'] ?? null) === 'rechazada') {
            $tipo = EstadoAsistenciaEnum::FALTO;
        }

        $asistencia = Asistencia::firstOrCreate(
            [
                'usuario_id' => $usuario->id,
                'fecha' => $fecha,
            ],
            [
                'tipo' => $tipo,
                'hora_inicio' => $jornada['inicio'] ?? null,
                'hora_fin' => $jornada['fin'] ?? null,
                'tiempo_trabajado' => $jornada['tiempo'] ?? null,
            ]
        );

        if (! $asistencia->wasRecentlyCreated) {
            return; // Ya existe: el seeder es idempotente.
        }

        // Evidencia de la jornada.
        if (isset($jornada['evidencia'])) {
            $estados = [
                'aprobada' => [
                    'estado' => 'aprobada',
                    'observacion' => 'Evidencia revisada y aprobada.',
                ],
                'pendiente' => [
                    'estado' => 'pendiente',
                    'observacion' => null,
                ],
                'rechazada' => [
                    'estado' => 'rechazada',
                    'observacion' => 'El documento no acredita las actividades de la jornada.',
                ],
            ];

            $estado = $estados[$jornada['evidencia']];

            Justificacion::create([
                'asistencia_id' => $asistencia->id,
                'tipo' => 'evidencia',
                'descripcion' => 'Avance de actividades realizadas durante la jornada de prácticas.',
                'archivo_path' => $this->generarArchivo($usuario, $fecha, 'evidencia'),
                'observacion' => $estado['observacion'],
                'estado' => $estado['estado'],
                'fecha' => $fecha,
            ]);
        }

        // Justificación de falta o tardanza.
        if (isset($jornada['justificacion'])) {
            $motivo = $tipo === EstadoAsistenciaEnum::TARDANZA
                ? 'Retraso por inconvenientes con el transporte público.'
                : 'Inasistencia por motivos de salud / emergencia familiar.';

            $observaciones = [
                'pendiente' => null,
                'aprobada' => $tipo === EstadoAsistenciaEnum::TARDANZA
                    ? 'Motivo aceptado. La tardanza se mantiene registrada.'
                    : 'Motivo aceptado. La falta queda justificada.',
                'rechazada' => 'No se presentó un documento de sustento válido.',
            ];

            Justificacion::create([
                'asistencia_id' => $asistencia->id,
                'tipo' => 'justificacion',
                'descripcion' => $motivo,
                'archivo_path' => $this->generarArchivo($usuario, $fecha, 'justificacion'),
                'observacion' => $observaciones[$jornada['justificacion']],
                'estado' => $jornada['justificacion'],
                'fecha' => $fecha,
            ]);
        }
    }

    /**
     * Genera un archivo PNG ficticio y devuelve su ruta relativa al disco public.
     */
    private function generarArchivo(User $usuario, string $fecha, string $tipo): string
    {
        $directorio = storage_path('app/public/evidencias');
        File::ensureDirectoryExists($directorio);

        $prefijo = $tipo === 'evidencia' ? 'Evidencia' : 'Justificacion';
        $texto = "{$prefijo} - {$usuario->nombre} {$usuario->apellidos} - {$fecha}";

        try {
            $ruta = ImageGenerator::image(
                dir: $directorio,
                text: $texto,
                width: 800,
                height: 600,
                fullPath: true,
                format: 'png',
                category: 'office',
            );
        } catch (\Throwable) {
            // Respaldo sin red: PNG válido generado con GD.
            $ruta = $this->generarPngConGd($directorio, $texto);
        }

        return 'evidencias/'.basename($ruta);
    }

    /**
     * Genera un PNG simple con el texto indicado usando GD.
     */
    private function generarPngConGd(string $directorio, string $texto): string
    {
        $imagen = imagecreatetruecolor(800, 600);
        $fondo = imagecolorallocate($imagen, 245, 247, 250);
        $tinta = imagecolorallocate($imagen, 40, 45, 60);

        imagefilledrectangle($imagen, 0, 0, 800, 600, $fondo);
        imagestring($imagen, 4, 40, 280, substr($texto, 0, 60), $tinta);
        imagestring($imagen, 3, 40, 310, 'PractiControl - datos de demostracion', $tinta);

        $nombre = 'demo_'.preg_replace('/[^a-z0-9]+/i', '_', $texto).'.png';
        $ruta = $directorio.'/'.$nombre;

        imagepng($imagen, $ruta);
        imagedestroy($imagen);

        return $ruta;
    }
}
