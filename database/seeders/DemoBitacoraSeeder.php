<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use App\Notifications\FaltaAutomaticaNotification;
use App\Notifications\SolicitudPracticaNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoBitacoraSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'pepe@gmail.com';

    private const PRACTICANTE_EMAIL = 'practicante@practicontrol.test';

    private const CARLOS_EMAIL = 'carlos.ramirez@practicontrol.test';

    private const JUAN_EMAIL = 'juan.perez@practicontrol.test';

    private const MARIA_EMAIL = 'maria.torres@practicontrol.test';

    public function run(): void
    {
        $admin = User::where('email', self::ADMIN_EMAIL)->firstOrFail();
        $practicante = User::where('email', self::PRACTICANTE_EMAIL)->firstOrFail();
        $carlos = User::where('email', self::CARLOS_EMAIL)->firstOrFail();
        $juan = User::where('email', self::JUAN_EMAIL)->firstOrFail();
        $maria = User::where('email', self::MARIA_EMAIL)->firstOrFail();

        $this->seedNotificaciones($admin, $practicante);
        $this->seedNotificacionesAdicionales($admin, $juan, $maria);
        $this->seedAuditoria($admin, $practicante);
        $this->seedAuditoriaCarlos($admin, $carlos);
        $this->seedActividadesDiarias($maria);
    }

    private function seedNotificaciones(User $admin, User $practicante): void
    {
        $justificaciones = Justificacion::whereHas(
            'asistencia',
            fn ($query) => $query->where('usuario_id', $practicante->id)
        )->get();

        $pendiente = $justificaciones->first(
            fn (Justificacion $item) => $item->tipo === 'evidencia' && $item->estado === 'pendiente'
        );

        $justificacionPendiente = $justificaciones->first(
            fn (Justificacion $item) => $item->tipo === 'justificacion' && $item->estado === 'pendiente'
        );

        if ($pendiente) {
            $this->insertarNotificacion(
                $admin,
                SolicitudPracticaNotification::class,
                'solicitud-evidencia-'.$pendiente->id,
                [
                    'titulo' => 'Nueva evidencia de asistencia',
                    'mensaje' => $practicante->nombre.' '.$practicante->apellidos
                        .' envió una evidencia para validar su asistencia.',
                    'tipo' => 'evidencia',
                    'justificacion_id' => $pendiente->id,
                    'asistencia_id' => $pendiente->asistencia_id,
                    'practicante' => $practicante->nombre.' '.$practicante->apellidos,
                    'estado' => 'pendiente',
                ],
                '2026-11-17 09:30:00'
            );
        }

        if ($justificacionPendiente) {
            $this->insertarNotificacion(
                $admin,
                SolicitudPracticaNotification::class,
                'solicitud-justificacion-'.$justificacionPendiente->id,
                [
                    'titulo' => 'Nueva justificación de falta',
                    'mensaje' => $practicante->nombre.' '.$practicante->apellidos
                        .' presentó una justificación de falta.',
                    'tipo' => 'justificacion',
                    'justificacion_id' => $justificacionPendiente->id,
                    'asistencia_id' => $justificacionPendiente->asistencia_id,
                    'practicante' => $practicante->nombre.' '.$practicante->apellidos,
                    'estado' => 'pendiente',
                ],
                '2026-11-17 10:15:00'
            );
        }

        $falta = Asistencia::where('usuario_id', $practicante->id)
            ->where('fecha', '2026-11-10')
            ->first();

        if ($falta) {
            $this->insertarNotificacion(
                $admin,
                FaltaAutomaticaNotification::class,
                'falta-automatica-'.$falta->id,
                [
                    'titulo' => 'Faltas registradas automáticamente',
                    'mensaje' => 'El 2026-11-10 no registró asistencia: '
                        .$practicante->nombre.' '.$practicante->apellidos.'.',
                    'fecha' => '2026-11-10',
                    'cantidad' => 1,
                ],
                '2026-11-10 13:05:00'
            );
        }
    }

    private function seedNotificacionesAdicionales(User $admin, User $juan, User $maria): void
    {
        $evidenciaPendiente = Justificacion::whereHas(
            'asistencia',
            fn ($query) => $query->where('usuario_id', $juan->id)
        )->where('tipo', 'evidencia')->where('estado', 'pendiente')->first();

        if ($evidenciaPendiente) {
            $this->insertarNotificacion(
                $admin,
                SolicitudPracticaNotification::class,
                'evidencia-pendiente-juan-'.$evidenciaPendiente->id,
                [
                    'titulo' => 'Nueva evidencia de asistencia',
                    'mensaje' => $juan->nombre.' '.$juan->apellidos
                        .' envió una evidencia para validar su asistencia.',
                    'tipo' => 'evidencia',
                    'justificacion_id' => $evidenciaPendiente->id,
                    'asistencia_id' => $evidenciaPendiente->asistencia_id,
                    'practicante' => $juan->nombre.' '.$juan->apellidos,
                    'estado' => 'pendiente',
                ],
                '2026-11-17 11:00:00'
            );
        }

        $justificacionPendiente = Justificacion::whereHas(
            'asistencia',
            fn ($query) => $query->where('usuario_id', $maria->id)
        )->where('tipo', 'justificacion')->where('estado', 'pendiente')->first();

        if ($justificacionPendiente) {
            $this->insertarNotificacion(
                $admin,
                SolicitudPracticaNotification::class,
                'justificacion-pendiente-maria-'.$justificacionPendiente->id,
                [
                    'titulo' => 'Nueva justificación de falta',
                    'mensaje' => $maria->nombre.' '.$maria->apellidos
                        .' presentó una justificación de falta.',
                    'tipo' => 'justificacion',
                    'justificacion_id' => $justificacionPendiente->id,
                    'asistencia_id' => $justificacionPendiente->asistencia_id,
                    'practicante' => $maria->nombre.' '.$maria->apellidos,
                    'estado' => 'pendiente',
                ],
                '2026-11-17 11:15:00'
            );
        }

        $falta = Asistencia::where('usuario_id', $maria->id)
            ->where('tipo', 'falto')
            ->orderBy('fecha')
            ->first();

        if ($falta) {
            $this->insertarNotificacion(
                $admin,
                FaltaAutomaticaNotification::class,
                'falta-automatica-maria-'.$falta->id,
                [
                    'titulo' => 'Faltas registradas automáticamente',
                    'mensaje' => 'El '.$falta->fecha.' no registró asistencia: '
                        .$maria->nombre.' '.$maria->apellidos.'.',
                    'fecha' => $falta->fecha,
                    'cantidad' => 1,
                ],
                $falta->fecha.' 13:05:00'
            );
        }
    }

    private function seedAuditoriaCarlos(User $admin, User $carlos): void
    {
        foreach (['2026-08-10', '2026-11-17'] as $fecha) {
            $asistencia = Asistencia::where('usuario_id', $carlos->id)
                ->where('fecha', $fecha)
                ->first();

            if (! $asistencia) {
                continue;
            }

            $justificacion = $asistencia->justificaciones()
                ->where('tipo', 'evidencia')
                ->where('estado', 'aprobada')
                ->first();

            if (! $justificacion) {
                continue;
            }

            $this->insertarActividad(
                'auditoria',
                'evidencia_validada',
                'El administrador validó la evidencia. La asistencia del practicante quedó registrada.',
                $fecha.' 13:10:00',
                Asistencia::class,
                $asistencia->id,
                User::class,
                $admin->id,
                [
                    'datos_anteriores' => ['estado' => 'pendiente'],
                    'datos_nuevos' => ['estado' => 'aprobada'],
                ],
                [
                    'justificacion_id' => $justificacion->id,
                    'tipo' => 'evidencia',
                ]
            );
        }
    }

    private function seedActividadesDiarias(User $maria): void
    {
        $actividades = [
            ['2026-11-06', 'Desarrollo de funcionalidades en Laravel para el módulo de calendario.'],
            ['2026-11-11', 'Corrección de errores visuales en el formulario de justificaciones.'],
            ['2026-11-12', 'Implementación de componentes de interfaz para reportes.'],
            ['2026-11-13', 'Pruebas funcionales de asistencias y evidencias.'],
            ['2026-11-17', 'Documentación técnica de los componentes del área de Diseño.'],
        ];

        foreach ($actividades as [$fecha, $descripcion]) {
            $asistencia = Asistencia::where('usuario_id', $maria->id)
                ->where('fecha', $fecha)
                ->first();

            if (! $asistencia) {
                continue;
            }

            $this->insertarActividad(
                'actividades',
                'actividad_diaria',
                $descripcion,
                $fecha.' 12:30:00',
                Asistencia::class,
                $asistencia->id,
                User::class,
                $maria->id,
                [],
                ['area' => 'Diseño']
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributeChanges
     * @param  array<string, mixed>  $properties
     */
    private function insertarActividad(
        string $logName,
        string $evento,
        string $descripcion,
        string $fecha,
        string $subjectType,
        int $subjectId,
        string $causerType,
        int $causerId,
        array $attributeChanges,
        array $properties
    ): void {
        $existe = DB::table('activity_log')
            ->where('log_name', $logName)
            ->where('event', $evento)
            ->where('description', $descripcion)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('causer_type', $causerType)
            ->where('causer_id', $causerId)
            ->exists();

        if ($existe) {
            return;
        }

        DB::table('activity_log')->insert([
            'log_name' => $logName,
            'description' => $descripcion,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'event' => $evento,
            'causer_type' => $causerType,
            'causer_id' => $causerId,
            'attribute_changes' => json_encode($attributeChanges, JSON_UNESCAPED_UNICODE),
            'properties' => json_encode($properties, JSON_UNESCAPED_UNICODE),
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function insertarNotificacion(
        User $notifiable,
        string $tipo,
        string $clave,
        array $data,
        string $fecha
    ): void {
        $hash = md5($clave);
        $id = substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'
            .substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20, 12);

        DB::table('notifications')->insertOrIgnore([
            'id' => $id,
            'type' => $tipo,
            'notifiable_type' => User::class,
            'notifiable_id' => $notifiable->id,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'read_at' => null,
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
    }

    private function seedAuditoria(User $admin, User $practicante): void
    {
        $registros = [
            [
                'evento' => 'evidencia_validada',
                'descripcion' => 'El administrador validó la evidencia. La asistencia del practicante quedó registrada.',
                'fecha' => '2026-11-03',
                'tipo' => 'evidencia',
                'estado' => 'aprobada',
            ],
            [
                'evento' => 'justificacion_aprobada',
                'descripcion' => 'El administrador aprobó la justificación de la falta.',
                'fecha' => '2026-11-11',
                'tipo' => 'justificacion',
                'estado' => 'aprobada',
            ],
            [
                'evento' => 'evidencia_rechazada',
                'descripcion' => 'El administrador rechazó la evidencia y registró una falta.',
                'fecha' => '2026-11-13',
                'tipo' => 'evidencia',
                'estado' => 'rechazada',
            ],
        ];

        foreach ($registros as $registro) {
            $asistencia = Asistencia::where('usuario_id', $practicante->id)
                ->where('fecha', $registro['fecha'])
                ->first();

            if (! $asistencia) {
                continue;
            }

            $justificacion = $asistencia->justificaciones()
                ->where('tipo', $registro['tipo'])
                ->where('estado', $registro['estado'])
                ->first();

            if (! $justificacion) {
                continue;
            }

            $existe = DB::table('activity_log')
                ->where('log_name', 'auditoria')
                ->where('event', $registro['evento'])
                ->where('description', $registro['descripcion'])
                ->where('subject_type', Asistencia::class)
                ->where('subject_id', $asistencia->id)
                ->where('causer_type', User::class)
                ->where('causer_id', $admin->id)
                ->exists();

            if ($existe) {
                continue;
            }

            DB::table('activity_log')->insert([
                'log_name' => 'auditoria',
                'description' => $registro['descripcion'],
                'subject_type' => Asistencia::class,
                'subject_id' => $asistencia->id,
                'event' => $registro['evento'],
                'causer_type' => User::class,
                'causer_id' => $admin->id,
                'attribute_changes' => json_encode([
                    'datos_anteriores' => ['estado' => 'pendiente'],
                    'datos_nuevos' => ['estado' => $registro['estado']],
                ], JSON_UNESCAPED_UNICODE),
                'properties' => json_encode([
                    'justificacion_id' => $justificacion->id,
                    'tipo' => $registro['tipo'],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => $registro['fecha'].' 13:10:00',
                'updated_at' => $registro['fecha'].' 13:10:00',
            ]);
        }
    }
}
