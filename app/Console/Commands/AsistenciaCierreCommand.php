<?php

namespace App\Console\Commands;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Models\User;
use App\Notifications\FaltaAutomaticaNotification;
use App\Services\Discord\AttendanceDiscordService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Throwable;

#[Signature('asistencia:cierre {--fecha= : Fecha a procesar (Y-m-d, por defecto hoy)}')]
#[Description('Cierra la jornada: corta las conexiones activas a las 13:00 y genera las faltas del día. Idempotente.')]
class AsistenciaCierreCommand extends Command
{
    public function handle(AttendanceDiscordService $attendanceService): int
    {
        try {
            $fecha = $this->option('fecha')
                ? Carbon::parse($this->option('fecha'))
                : today();

            $fecha = $fecha->startOfDay();

            // El corte contabilizable es a las 13:00 del día procesado
            // (al ejecutarlo después de las 13:00 el resultado es el mismo).
            $finForzado = $fecha->copy()->setTime(13, 0, 0);

            $conexionesCerradas = $this->cerrarConexionesActivas(
                $fecha,
                $finForzado,
                $attendanceService
            );

            $faltasCreadas = $this->generarFaltas($fecha);

            $this->info("Cierre de jornada {$fecha->toDateString()} finalizado.");
            $this->line("Conexiones cerradas: {$conexionesCerradas}");
            $this->line('Faltas nuevas registradas: '.count($faltasCreadas));

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);

            $this->error('El cierre de jornada falló: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Corta todas las conexiones activas del día y acumula su tiempo
     * hasta como máximo las 13:00.
     */
    private function cerrarConexionesActivas(
        Carbon $fecha,
        Carbon $finForzado,
        AttendanceDiscordService $attendanceService
    ): int {
        // Solo asistencias que admiten contabilización.
        $asistencias = Asistencia::whereDate('fecha', $fecha->toDateString())
            ->whereNotIn('tipo', [
                EstadoAsistenciaEnum::FALTO->value,
                EstadoAsistenciaEnum::JUSTIFICADO->value,
            ])
            ->get();

        $cerradas = 0;

        foreach ($asistencias as $asistencia) {
            $key = "attendance:{$asistencia->id}:connection";

            // La caché guarda el inicio de cada conexión activa.
            // endConnection la recupera y elimina (idempotente).
            if (Cache::has($key)) {
                $attendanceService->endConnection($asistencia, $finForzado);
                $cerradas++;
            }
        }

        return $cerradas;
    }

    /**
     * Genera la falta de cada practicante activo del día que no registró
     * asistencia. Usa firstOrCreate: nunca duplica registros.
     *
     * @return array<int, Asistencia> Faltas creadas en esta ejecución.
     */
    private function generarFaltas(Carbon $fecha): array
    {
        $creadas = [];

        // Los fines de semana no se generan faltas.
        if (! $fecha->isWeekday()) {
            $this->line('Fin de semana: no se generan faltas.');

            return $creadas;
        }

        $conAsistencia = Asistencia::whereDate('fecha', $fecha->toDateString())
            ->pluck('usuario_id')
            ->all();

        $practicantes = User::role('practicante')
            ->where('estado', true)
            ->get();

        foreach ($practicantes as $practicante) {
            // Solo dentro del periodo de prácticas (si está configurado).
            if ($practicante->fecha_inicio && $fecha->lt($practicante->fecha_inicio)) {
                continue;
            }

            if ($practicante->fecha_fin && $fecha->gt($practicante->fecha_fin)) {
                continue;
            }

            if (in_array($practicante->id, $conAsistencia, true)) {
                continue;
            }

            $falta = Asistencia::firstOrCreate(
                [
                    'usuario_id' => $practicante->id,
                    'fecha' => $fecha->toDateString(),
                ],
                [
                    'tipo' => EstadoAsistenciaEnum::FALTO,
                ]
            );

            if ($falta->wasRecentlyCreated) {
                $creadas[] = $falta;
            }
        }

        // Notificar a los administradores solo por faltas nuevas de esta
        // ejecución, para no generar notificaciones duplicadas.
        if ($creadas !== []) {
            Notification::send(
                User::role('admin')->get(),
                new FaltaAutomaticaNotification($creadas, $fecha->translatedFormat('d/m/Y'))
            );
        }

        return $creadas;
    }
}
