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
    ){}

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
        if (!$usuario->discord_id) {
            return back()->with(
                'error',
                'Primero debes registrar tu cuenta de Discord.'
            );
        }

        // Verificar que su cuenta esté activa.
        if (!$usuario->estado) {
            return back()->with(
                'error',
                'Tu cuenta se encuentra inactiva.'
            );
        }

        // La jornada de mañana termina a las 13:00.
        if (now()->format('H:i:s') >= '13:00:00') {
            return back()->with(
                'error',
                'El horario de asistencia ha finalizado.'
            );
        }

        // Crear el registro solamente si todavía no existe.
        $asistencia = Asistencia::firstOrCreate(
            [
                'usuario_id' => $usuario->id,
                'fecha' => today()->toDateString(),
            ],
            [
                'tipo' => EstadoAsistenciaEnum::PENDIENTE,
            ]
        );

        if (!$asistencia->wasRecentlyCreated) {
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
