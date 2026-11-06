<?php

namespace App\Services\Discord;
use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttendanceDiscordService
{
    public function processPresence(string $discordId, string $status): void
    {
        $usuario = User::where('discord_id', $discordId)
            ->where('estado', true)
            ->first();

        if (!$usuario) {
            return;
        }

        $asistencia = Asistencia::where('usuario_id', $usuario->id)
            ->whereDate('fecha', today())
            ->first();

        // Solo contabilizar si el practicante inició su asistencia.
        if (!$asistencia) {
            return;
        }

        if (in_array($status, ['online', 'idle', 'dnd'])) {
            $this->startConnection($asistencia);
        }

        if ($status === 'offline') {
            $this->endConnection($asistencia);
        }
    }

    private function startConnection(Asistencia $asistencia): void
    {
        $now = now();

        // Turno de mañana: 08:00 a 13:00.
        if ($now->format('H:i:s') < '08:00:00' ||
            $now->format('H:i:s') >= '13:00:00') {
            return;
        }

        $key = "attendance:{$asistencia->id}:connection";

        // Evitar reiniciar el contador con cambios online/idle/dnd.
        if (Cache::has($key)) {
            return;
        }

        DB::transaction(function () use ($asistencia, $now, $key) {
            $registro = Asistencia::whereKey($asistencia->id)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                return;
            }

            if ($registro->hora_inicio === null) {
                $registro->hora_inicio = $now->format('H:i:s');
            }

            if ($registro->tipo === EstadoAsistenciaEnum::PENDIENTE) {
                $registro->tipo = EstadoAsistenciaEnum::PENDIENTE;
            }

            $registro->save();

            Cache::put($key, $now->toIso8601String(), now()->endOfDay());
        });
    }

    private function endConnection(Asistencia $asistencia): void
    {
        $key = "attendance:{$asistencia->id}:connection";

        // Si no existe inicio, no hay tiempo que contabilizar.
        $inicioGuardado = Cache::pull($key);

        if (!$inicioGuardado) {
            return;
        }

        DB::transaction(function () use ($asistencia, $inicioGuardado) {
            $registro = Asistencia::whereKey($asistencia->id)
                ->lockForUpdate()
                ->first();

            if (!$registro) {
                return;
            }

            $inicio = Carbon::parse($inicioGuardado);
            $fin = now();

            // Nunca contabilizar después de las 13:00.
            $limite = $fin->copy()->setTime(13, 0, 0);

            if ($fin->greaterThan($limite)) {
                $fin = $limite;
            }

            $segundosNuevos = max(0, (int) $inicio->diffInSeconds($fin));

            $segundosAnteriores = 0;

            if ($registro->tiempo_trabajado) {
                $partes = explode(':', $registro->tiempo_trabajado);

                $segundosAnteriores =
                    ((int) $partes[0] * 3600) +
                    ((int) $partes[1] * 60) +
                    ((int) ($partes[2] ?? 0));
            }

            $total = $segundosAnteriores + $segundosNuevos;

            $registro->tiempo_trabajado = sprintf(
                '%02d:%02d:%02d',
                intdiv($total, 3600),
                intdiv($total % 3600, 60),
                $total % 60
            );

            $registro->hora_fin = $fin->format('H:i:s');

            $registro->save();
        });
    }
}
