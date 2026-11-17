<?php

namespace App\Http\Controllers;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Services\tickService;
use Illuminate\Http\Request;

class TickController extends Controller
{
    public function __construct(
        protected tickService $tickService
    ) {}

    public function index()
    {
        $asistencia = $this->tickService->getInformation();

        // return $asistencia;

        return view('practicing.tick.index', compact('asistencia'));
    }

    public function marcar(Request $request)
    {
        $usuario = auth()->user();

        // Verificar que el practicante tenga vinculado Discord.
        if (! $usuario->discord_id) {
            return back()->with(
                'error',
                'Primero debes registrar tu cuenta de Discord.'
            );
        }

        // Verificar que su cuenta esté activa.
        if (! $usuario->estado) {
            return back()->with(
                'error',
                'Tu cuenta se encuentra inactiva.'
            );
        }

        // La jornada termina a las 13:00: a partir de esa hora ya no se puede
        // iniciar una nueva asistencia. La ausencia se registrará al cierre.
        if (now()->greaterThanOrEqualTo(now()->copy()->setTime(13, 0, 0))) {
            return back()->with(
                'error',
                'El horario de asistencia ha finalizado. Si no registraste asistencia, se considerará falta al cierre de la jornada.'
            );
        }

        // Clasificación según la hora de registro (America/Lima):
        // - antes de las 08:00  -> asistio (el tiempo cuenta desde las 08:00)
        // - desde las 08:00     -> tardanza (hasta antes de las 13:00)
        $tipo = now()->lessThan(now()->copy()->setTime(8, 0, 0))
            ? EstadoAsistenciaEnum::ASISTIO
            : EstadoAsistenciaEnum::TARDANZA;

        // Crear el registro solamente si todavía no existe.
        $asistencia = Asistencia::firstOrCreate(
            [
                'usuario_id' => $usuario->id,
                'fecha' => today()->toDateString(),
            ],
            [
                'tipo' => $tipo,
            ]
        );

        if (! $asistencia->wasRecentlyCreated) {
            return back()->with(
                'info',
                'Ya registraste tu asistencia de hoy.'
            );
        }

        return back()->with(
            'success',
            'Asistencia iniciada. Estamos esperando la conexión de Discord.'
        );
    }
}
