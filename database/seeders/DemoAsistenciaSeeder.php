<?php

namespace Database\Seeders;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoAsistenciaSeeder extends Seeder
{
    private const PRACTICANTE_EMAIL = 'practicante@practicontrol.test';

    private const CARLOS_EMAIL = 'carlos.ramirez@practicontrol.test';

    private const JUAN_EMAIL = 'juan.perez@practicontrol.test';

    private const MARIA_EMAIL = 'maria.torres@practicontrol.test';

    private const FECHA_LIMITE = '2026-11-17';

    /**
     * Jornadas ficticias anteriores a la fecha actual, con horas coherentes
     * con la jornada de 08:00 a 13:00 en America/Lima.
     *
     * @var array<string, array<string, string|null>>
     */
    private const JORNADAS = [
        '2026-11-02' => [
            'tipo' => 'asistio',
            'inicio' => '08:00:00',
            'fin' => '13:00:00',
            'tiempo' => '05:00:00',
            'evidencia' => 'aprobada',
        ],
        '2026-11-03' => [
            'tipo' => 'asistio',
            'inicio' => '08:00:00',
            'fin' => '13:00:00',
            'tiempo' => '05:00:00',
            'evidencia' => 'aprobada',
        ],
        '2026-11-04' => [
            'tipo' => 'tardanza',
            'inicio' => '08:20:00',
            'fin' => '13:00:00',
            'tiempo' => '04:40:00',
            'evidencia' => 'pendiente',
            'justificacion' => 'pendiente',
        ],
        '2026-11-05' => [
            'tipo' => 'asistio',
            'inicio' => '08:00:00',
            'fin' => '13:00:00',
            'tiempo' => '05:00:00',
            'evidencia' => 'aprobada',
        ],
        '2026-11-06' => [
            'tipo' => 'tardanza',
            'inicio' => '08:45:00',
            'fin' => '13:00:00',
            'tiempo' => '04:15:00',
            'justificacion' => 'aprobada',
        ],
        '2026-11-09' => [
            'tipo' => 'asistio',
            'inicio' => '08:00:00',
            'fin' => '13:00:00',
            'tiempo' => '05:00:00',
            'evidencia' => 'aprobada',
        ],
        '2026-11-10' => [
            'tipo' => 'falto',
            'justificacion' => 'rechazada',
        ],
        '2026-11-11' => [
            'tipo' => 'justificado',
            'justificacion' => 'aprobada',
        ],
        '2026-11-12' => [
            'tipo' => 'tardanza',
            'inicio' => '08:10:00',
            'fin' => '12:30:00',
            'tiempo' => '04:20:00',
            'evidencia' => 'aprobada',
        ],
        '2026-11-13' => [
            'tipo' => 'falto',
            'evidencia' => 'rechazada',
        ],
        '2026-11-16' => [
            'tipo' => 'asistio',
            'inicio' => '08:00:00',
            'fin' => '13:00:00',
            'tiempo' => '05:00:00',
            'evidencia' => 'pendiente',
        ],
        '2026-11-17' => [
            'tipo' => 'pendiente',
        ],
    ];

    public function run(): void
    {
        $practicante = User::where('email', self::PRACTICANTE_EMAIL)->firstOrFail();

        foreach (self::JORNADAS as $fecha => $jornada) {
            $asistencia = Asistencia::firstOrCreate(
                [
                    'usuario_id' => $practicante->id,
                    'fecha' => $fecha,
                ],
                [
                    'tipo' => EstadoAsistenciaEnum::from($jornada['tipo']),
                    'hora_inicio' => $jornada['inicio'] ?? null,
                    'hora_fin' => $jornada['fin'] ?? null,
                    'tiempo_trabajado' => $jornada['tiempo'] ?? null,
                ]
            );

            if (isset($jornada['evidencia'])) {
                $this->crearJustificacion(
                    $asistencia,
                    $fecha,
                    'evidencia',
                    $jornada['evidencia']
                );
            }

            if (isset($jornada['justificacion'])) {
                $this->crearJustificacion(
                    $asistencia,
                    $fecha,
                    'justificacion',
                    $jornada['justificacion']
                );
            }
        }

        // 72 jornadas completas = 360:00:00 horas contabilizables.
        $this->generarJornadasCompletas(
            User::where('email', self::CARLOS_EMAIL)->firstOrFail(),
            72,
            'aprobada'
        );

        // 49 jornadas completas = 245:00:00 horas contabilizables.
        $this->generarJornadasCompletas(
            User::where('email', self::JUAN_EMAIL)->firstOrFail(),
            49,
            'aprobada',
            [4, 12, 25, 40]
        );

        $this->generarIncidencias(
            User::where('email', self::MARIA_EMAIL)->firstOrFail()
        );
    }

    /**
     * Genera jornadas completas de 08:00 a 13:00 con evidencia local.
     *
     * @param  array<int, int>  $indicesPendientes
     */
    private function generarJornadasCompletas(
        User $usuario,
        int $cantidad,
        string $estadoEvidencia,
        array $indicesPendientes = []
    ): void {
        foreach ($this->fechasLaborables($cantidad) as $indice => $fecha) {
            $estado = in_array($indice, $indicesPendientes, true)
                ? 'pendiente'
                : $estadoEvidencia;

            $asistencia = Asistencia::firstOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'fecha' => $fecha,
                ],
                [
                    'tipo' => EstadoAsistenciaEnum::ASISTIO,
                    'hora_inicio' => '08:00:00',
                    'hora_fin' => '13:00:00',
                    'tiempo_trabajado' => '05:00:00',
                ]
            );

            $this->crearJustificacion(
                $asistencia,
                $fecha,
                'evidencia',
                $estado,
                $this->archivoCompartido('evidencia', $estado)
            );
        }
    }

    /**
     * Jornadas con incidencias para el tercer practicante.
     */
    private function generarIncidencias(User $usuario): void
    {
        $patrones = [
            ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
            ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
            ['tipo' => 'tardanza', 'inicio' => '08:25:00', 'fin' => '13:00:00', 'tiempo' => '04:35:00', 'evidencia' => 'pendiente', 'justificacion' => 'pendiente'],
            ['tipo' => 'falto', 'justificacion' => 'pendiente'],
            ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
            ['tipo' => 'tardanza', 'inicio' => '08:40:00', 'fin' => '13:00:00', 'tiempo' => '04:20:00', 'justificacion' => 'aprobada'],
            ['tipo' => 'falto', 'justificacion' => 'rechazada'],
            ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
            ['tipo' => 'pendiente'],
            ['tipo' => 'tardanza', 'inicio' => '08:15:00', 'fin' => '13:00:00', 'tiempo' => '04:45:00', 'justificacion' => 'rechazada'],
            ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
            ['tipo' => 'justificado', 'justificacion' => 'aprobada'],
            ['tipo' => 'falto', 'evidencia' => 'rechazada'],
            ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
            ['tipo' => 'tardanza', 'inicio' => '08:50:00', 'fin' => '13:00:00', 'tiempo' => '04:10:00', 'evidencia' => 'pendiente'],
            ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
            ['tipo' => 'pendiente'],
            ['tipo' => 'asistio', 'inicio' => '08:00:00', 'fin' => '13:00:00', 'tiempo' => '05:00:00', 'evidencia' => 'aprobada'],
        ];

        foreach ($this->fechasLaborables(count($patrones)) as $indice => $fecha) {
            $patron = $patrones[$indice];

            $asistencia = Asistencia::firstOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'fecha' => $fecha,
                ],
                [
                    'tipo' => EstadoAsistenciaEnum::from($patron['tipo']),
                    'hora_inicio' => $patron['inicio'] ?? null,
                    'hora_fin' => $patron['fin'] ?? null,
                    'tiempo_trabajado' => $patron['tiempo'] ?? null,
                ]
            );

            if (isset($patron['evidencia'])) {
                $this->crearJustificacion(
                    $asistencia,
                    $fecha,
                    'evidencia',
                    $patron['evidencia'],
                    $this->archivoCompartido('evidencia', $patron['evidencia'])
                );
            }

            if (isset($patron['justificacion'])) {
                $this->crearJustificacion(
                    $asistencia,
                    $fecha,
                    'justificacion',
                    $patron['justificacion'],
                    $this->archivoCompartido('justificacion', $patron['justificacion'])
                );
            }
        }
    }

    /**
     * Últimos días laborables, en orden cronológico y sin fechas futuras.
     *
     * @return array<int, string>
     */
    private function fechasLaborables(int $cantidad): array
    {
        $fechas = [];
        $fecha = new DateTimeImmutable(self::FECHA_LIMITE, new DateTimeZone('America/Lima'));

        while (count($fechas) < $cantidad) {
            if ((int) $fecha->format('N') <= 5) {
                $fechas[] = $fecha->format('Y-m-d');
            }

            $fecha = $fecha->modify('-1 day');
        }

        return array_reverse($fechas);
    }

    private function crearJustificacion(
        Asistencia $asistencia,
        string $fecha,
        string $tipo,
        string $estado,
        ?string $archivoPath = null
    ): void {
        $esEvidencia = $tipo === 'evidencia';

        $descripcion = $esEvidencia
            ? 'Avance de actividades realizadas durante la jornada de prácticas.'
            : ($asistencia->tipo === EstadoAsistenciaEnum::TARDANZA
                ? 'Retraso por inconvenientes con el transporte público.'
                : 'Inasistencia por motivos de emergencia familiar.');

        $observacion = match ($estado) {
            'aprobada' => $esEvidencia
                ? 'Evidencia revisada y aprobada.'
                : 'Motivo aceptado por la coordinación académica.',
            'rechazada' => $esEvidencia
                ? 'El documento no acredita las actividades de la jornada.'
                : 'No se presentó un documento de sustento válido.',
            default => null,
        };

        Justificacion::firstOrCreate(
            [
                'asistencia_id' => $asistencia->id,
                'tipo' => $tipo,
                'fecha' => $fecha,
                'descripcion' => $descripcion,
            ],
            [
                'archivo_path' => $archivoPath ?? $this->archivoDemostracion($asistencia, $fecha, $tipo, $estado),
                'estado' => $estado,
                'observacion' => $observacion,
            ]
        );
    }

    /**
     * Archivo compartido para las jornadas masivas; evita cientos de PNG duplicados.
     */
    private function archivoCompartido(string $tipo, string $estado): string
    {
        $ruta = 'evidencias/demo_'.$tipo.'_'.$estado.'.png';

        if (! Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->put($ruta, $this->contenidoPng('2026-11-17', $tipo, $estado));
        }

        return $ruta;
    }

    /**
     * Crea, si aún no existe, un PNG local de demostración en el disco public.
     */
    private function archivoDemostracion(
        Asistencia $asistencia,
        string $fecha,
        string $tipo,
        string $estado
    ): string {
        $nombre = sprintf(
            'demo_%s_%d_%s_%s.png',
            $tipo,
            $asistencia->usuario_id,
            str_replace('-', '', $fecha),
            $estado
        );

        $ruta = 'evidencias/'.$nombre;

        if (! Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->put($ruta, $this->contenidoPng($fecha, $tipo, $estado));
        }

        return $ruta;
    }

    /**
     * PNG valido generado localmente, sin servicios externos ni datos personales.
     */
    private function contenidoPng(string $fecha, string $tipo, string $estado): string
    {
        if (! extension_loaded('gd')) {
            return base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
            );
        }

        $imagen = imagecreatetruecolor(800, 400);
        $fondo = imagecolorallocate($imagen, 245, 247, 250);
        $tinta = imagecolorallocate($imagen, 40, 45, 60);

        imagefilledrectangle($imagen, 0, 0, 800, 400, $fondo);
        imagestring($imagen, 5, 40, 160, 'PractiControl - Demo Data', $tinta);
        imagestring($imagen, 3, 40, 200, ucfirst($tipo).' - '.$estado.' - '.$fecha, $tinta);
        imagestring($imagen, 3, 40, 230, 'Archivo ficticio sin datos personales', $tinta);

        ob_start();
        imagepng($imagen);
        $contenido = (string) ob_get_clean();
        imagedestroy($imagen);

        return $contenido;
    }
}
