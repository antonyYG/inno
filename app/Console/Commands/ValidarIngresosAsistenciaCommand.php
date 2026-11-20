<?php

namespace App\Console\Commands;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Models\User;
use App\Notifications\FaltaAutomaticaNotification;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

#[Signature('asistencia:validar-ingresos {--fecha= : Fecha a procesar (Y-m-d, por defecto hoy)}')]
#[Description('Valida a las 09:00 los ingresos a Discord y registra las faltas correspondientes. Idempotente.')]
class ValidarIngresosAsistenciaCommand extends Command
{
    private const ZONA_HORARIA = 'America/Lima';

    public function handle(): int
    {
        try {
            $fecha = $this->fechaProcesar();

            [$faltasCreadas, $pendientesConvertidos] = $this->validarIngresos($fecha);

            $this->info("Validación de ingresos {$fecha->toDateString()} finalizada.");
            $this->line('Faltas creadas: '.count($faltasCreadas));
            $this->line('Pendientes convertidos en faltas: '.count($pendientesConvertidos));

            $this->notificar(
                [...$faltasCreadas, ...$pendientesConvertidos],
                $fecha
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);

            $this->error('La validación de ingresos falló: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function fechaProcesar(): Carbon
    {
        $fecha = $this->option('fecha')
            ? Carbon::parse((string) $this->option('fecha'), self::ZONA_HORARIA)
            : Carbon::now(self::ZONA_HORARIA);

        return $fecha->startOfDay();
    }

    /**
     * @return array{0: array<int, Asistencia>, 1: array<int, Asistencia>}
     */
    private function validarIngresos(Carbon $fecha): array
    {
        $faltasCreadas = [];
        $pendientesConvertidos = [];

        foreach ($this->practicantesActivos($fecha) as $practicante) {
            $asistencia = Asistencia::query()
                ->where('usuario_id', $practicante->id)
                ->whereDate('fecha', $fecha->toDateString())
                ->first();

            if ($asistencia === null) {
                $faltasCreadas[] = Asistencia::create([
                    'usuario_id' => $practicante->id,
                    'fecha' => $fecha->toDateString(),
                    'tipo' => EstadoAsistenciaEnum::FALTO,
                ]);

                continue;
            }

            if (
                $asistencia->tipo === EstadoAsistenciaEnum::PENDIENTE
                && $asistencia->hora_inicio === null
            ) {
                $asistencia->update(['tipo' => EstadoAsistenciaEnum::FALTO]);

                $pendientesConvertidos[] = $asistencia;
            }
        }

        return [$faltasCreadas, $pendientesConvertidos];
    }

    /**
     * @return Collection<int, User>
     */
    private function practicantesActivos(Carbon $fecha): Collection
    {
        return User::role('practicante')
            ->where('estado', true)
            ->get()
            ->filter(function (User $practicante) use ($fecha): bool {
                if ($practicante->fecha_inicio !== null && $fecha->lt($practicante->fecha_inicio)) {
                    return false;
                }

                if ($practicante->fecha_fin !== null && $fecha->gt($practicante->fecha_fin)) {
                    return false;
                }

                return true;
            });
    }

    /**
     * @param  array<int, Asistencia>  $faltas
     */
    private function notificar(array $faltas, Carbon $fecha): void
    {
        if ($faltas === []) {
            return;
        }

        Notification::send(
            User::role('admin')->get(),
            new FaltaAutomaticaNotification(
                $faltas,
                $fecha->translatedFormat('d/m/Y'),
                $fecha->toDateString()
            )
        );
    }
}
