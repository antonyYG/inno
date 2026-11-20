<?php

namespace App\Services\Discord;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AttendanceDiscordService
{
    // Ventana contabilizable de la jornada (America/Lima).
    private const HORA_INICIO = '08:00:00';

    private const HORA_FIN = '13:00:00';

    public function processPresence(string $discordId, string $status): void
    {
        $usuario = User::where('discord_id', $discordId)
            ->where('estado', true)
            ->first();

        if (! $usuario) {
            return;
        }

        $asistencia = Asistencia::where('usuario_id', $usuario->id)
            ->whereDate('fecha', today())
            ->first();

        // Solo contar si el practicante registró su asistencia.
        if (! $asistencia) {
            return;
        }

        // No acumular tiempo en faltas o asistencias justificadas.
        // Se cuenta para pendiente, asistio y tardanza.
        if (in_array($asistencia->tipo, [
            EstadoAsistenciaEnum::FALTO,
            EstadoAsistenciaEnum::JUSTIFICADO,
        ], true)) {
            return;
        }

        if (in_array($status, ['online', 'idle', 'dnd'], true)) {
            $this->startConnection($asistencia);

            return;
        }

        if ($status === 'offline') {
            $this->endConnection($asistencia);
        }
    }

    /**
     * Inicia una conexión sin reiniciar un contador existente.
     */

    private function startConnection(Asistencia $asistencia): void
    {
        $now = now();

        /*
    |--------------------------------------------------------------------------
    | MODO PRUEBAS
    |--------------------------------------------------------------------------
    | Permite contabilizar desde cualquier hora.
    | Descomentar para realizar pruebas.
    */

        $inicioContabilizable = $now;

        /*
    |--------------------------------------------------------------------------
    | MODO PRODUCCIÓN
    |--------------------------------------------------------------------------
    | Solo contabiliza entre las 08:00 y las 13:00.
    | Comentar este bloque cuando estés probando.
    */

        /*
    $horaInicio = $now->copy()->setTime(8, 0, 0);
    $horaFin = $now->copy()->setTime(13, 0, 0);

    if ($now->greaterThanOrEqualTo($horaFin)) {
        return;
    }

    $inicioContabilizable = $now->lessThan($horaInicio)
        ? $horaInicio
        : $now;
    */

        $key = "attendance:{$asistencia->id}:connection";

        // Evitar iniciar una conexión duplicada.
        if (! Cache::add($key, $inicioContabilizable->toIso8601String(), now()->endOfDay())) {
            return;
        }

        // Registrar la primera hora de conexión.
        if (! $asistencia->hora_inicio) {
            $asistencia->hora_inicio = $inicioContabilizable->format('H:i:s');
            $asistencia->save();
        }
    }

    // private function startConnection(Asistencia $asistencia): void
    // {
    //     $now = now();

    //     $horaInicio = $now->copy()->setTime(8, 0, 0);
    //     $horaFin = $now->copy()->setTime(13, 0, 0);

    //     if ($now->greaterThanOrEqualTo($horaFin)) {
    //         return;
    //     }

    //     $inicioContabilizable = $now->lessThan($horaInicio)
    //         ? $horaInicio
    //         : $now;

    //     $key = "attendance:{$asistencia->id}:connection";

    //     // Evitar iniciar una conexión duplicada.
    //     if (! Cache::add($key, $inicioContabilizable->toIso8601String(), now()->endOfDay())) {
    //         return;
    //     }

    //     // Registrar inmediatamente la primera hora de conexión.
    //     if (! $asistencia->hora_inicio) {
    //         $asistencia->hora_inicio = $inicioContabilizable->format('H:i:s');
    //         $asistencia->save();
    //     }
    // }

    /**
     * Cierra la conexión y acumula el tiempo trabajado.
     * Pública: el comando de cierre de jornada la usa para cortar
     * las conexiones que siguen activas a las 13:00.
     */
    // public function endConnection(Asistencia $asistencia, ?Carbon $finForzado = null): void
    // {
    //     $key = "attendance:{$asistencia->id}:connection";

    //     // Recupera y elimina el inicio de la conexión.
    //     $inicioGuardado = Cache::pull($key);

    //     if (! $inicioGuardado) {
    //         return;
    //     }

    //     try {
    //         DB::transaction(function () use ($asistencia, $inicioGuardado, $finForzado) {

    //             $registro = Asistencia::whereKey($asistencia->id)
    //                 ->lockForUpdate()
    //                 ->first();

    //             if (! $registro) {
    //                 return;
    //             }

    //             $inicio = Carbon::parse($inicioGuardado);

    //             $ahora = now();

    //             // Nunca contabilizar antes de las 08:00 ni después de las 13:00.
    //             // El cierre de jornada fuerza el corte a las 13:00 del día de la asistencia.
    //             $limiteFin = $ahora->copy()->setTime(13, 0, 0);

    //             if ($finForzado) {
    //                 $limiteFin = $limiteFin->min($finForzado);
    //             }

    //             $fin = $ahora->greaterThan($limiteFin)
    //                 ? $limiteFin
    //                 : $ahora;

    //             // Calcular los segundos de esta conexión.
    //             $segundosNuevos = max(
    //                 0,
    //                 $fin->getTimestamp() - $inicio->getTimestamp()
    //             );

    //             // Si no hubo tiempo efectivo, no modificar el registro.
    //             if ($segundosNuevos <= 0) {
    //                 return;
    //             }

    //             // Recuperar el tiempo anteriormente acumulado.
    //             $segundosAnteriores = 0;

    //             if ($registro->tiempo_trabajado) {
    //                 $partes = explode(':', $registro->tiempo_trabajado);

    //                 $horas = (int) ($partes[0] ?? 0);
    //                 $minutos = (int) ($partes[1] ?? 0);
    //                 $segundos = (int) ($partes[2] ?? 0);

    //                 $segundosAnteriores =
    //                     ($horas * 3600) +
    //                     ($minutos * 60) +
    //                     $segundos;
    //             }

    //             // Acumular el tiempo de todas las conexiones.
    //             $total = $segundosAnteriores + $segundosNuevos;

    //             $registro->tiempo_trabajado = sprintf(
    //                 '%02d:%02d:%02d',
    //                 intdiv($total, 3600),
    //                 intdiv($total % 3600, 60),
    //                 $total % 60
    //             );

    //             // Guardar la primera conexión contabilizada.
    //             if (! $registro->hora_inicio) {
    //                 $registro->hora_inicio = $inicio->format('H:i:s');
    //             }

    //             // Guardar la última desconexión.
    //             $registro->hora_fin = $fin->format('H:i:s');

    //             $registro->save();
    //         });
    //     } catch (Throwable $e) {
    //         // Restaurar la conexión si ocurre un error al guardar.
    //         Cache::put(
    //             $key,
    //             $inicioGuardado,
    //             now()->endOfDay()
    //         );

    //         Log::error('No se pudo acumular el tiempo de la asistencia', [
    //             'asistencia_id' => $asistencia->id,
    //             'usuario_id' => $asistencia->usuario_id,
    //             'error' => $e->getMessage(),
    //         ]);

    //         throw $e;
    //     }
    // }
    public function endConnection(Asistencia $asistencia, ?Carbon $finForzado = null): void
    {
        $key = "attendance:{$asistencia->id}:connection";

        // Recuperar y eliminar el inicio de la conexión.
        $inicioGuardado = Cache::pull($key);

        if (! $inicioGuardado) {
            return;
        }

        try {
            DB::transaction(function () use ($asistencia, $inicioGuardado, $finForzado) {

                $registro = Asistencia::whereKey($asistencia->id)
                    ->lockForUpdate()
                    ->first();

                if (! $registro) {
                    return;
                }

                $inicio = Carbon::parse($inicioGuardado);
                $ahora = now();

                /*
            |--------------------------------------------------------------------------
            | MODO PRUEBAS
            |--------------------------------------------------------------------------
            | Permite contabilizar hasta la hora actual.
            | Descomentar durante el desarrollo.
            */

                $limiteFin = $ahora->copy();

                /*
            |--------------------------------------------------------------------------
            | MODO PRODUCCIÓN
            |--------------------------------------------------------------------------
            | Solo permite contabilizar hasta las 13:00.
            | Descomentar cuando el sistema esté en producción.
            */

                /*
            $limiteFin = Carbon::parse($registro->fecha)
                ->setTime(13, 0, 0);
            */

                if ($finForzado) {
                    $limiteFin = $limiteFin->min($finForzado);
                }

                $fin = $ahora->greaterThan($limiteFin)
                    ? $limiteFin
                    : $ahora;

                // Calcular los segundos de esta conexión.
                $segundosNuevos = max(
                    0,
                    $fin->getTimestamp() - $inicio->getTimestamp()
                );

                if ($segundosNuevos <= 0) {
                    return;
                }

                // Recuperar el tiempo anteriormente acumulado.
                $segundosAnteriores = 0;

                if ($registro->tiempo_trabajado) {
                    $partes = explode(':', $registro->tiempo_trabajado);

                    $horas = (int) ($partes[0] ?? 0);
                    $minutos = (int) ($partes[1] ?? 0);
                    $segundos = (int) ($partes[2] ?? 0);

                    $segundosAnteriores =
                        ($horas * 3600) +
                        ($minutos * 60) +
                        $segundos;
                }

                // Acumular el tiempo de todas las conexiones.
                $total = $segundosAnteriores + $segundosNuevos;

                $registro->tiempo_trabajado = sprintf(
                    '%02d:%02d:%02d',
                    intdiv($total, 3600),
                    intdiv($total % 3600, 60),
                    $total % 60
                );

                // Guardar la primera conexión contabilizada.
                if (! $registro->hora_inicio) {
                    $registro->hora_inicio = $inicio->format('H:i:s');
                }

                // Guardar la última desconexión.
                $registro->hora_fin = $fin->format('H:i:s');

                $registro->save();
            });
        } catch (Throwable $e) {

            // Restaurar la conexión si ocurre un error al guardar.
            Cache::put(
                $key,
                $inicioGuardado,
                now()->endOfDay()
            );

            Log::error('No se pudo acumular el tiempo de la asistencia', [
                'asistencia_id' => $asistencia->id,
                'usuario_id' => $asistencia->usuario_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
