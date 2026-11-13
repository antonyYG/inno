<?php

namespace App\Http\Controllers;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Services\CalendarService;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    public function index()
    {
        if (auth()->user()->hasRole('practicante')) {
            return view('practicing.calendar.index');
        }

        return view('admin.calendar.index');
    }

    public function data()
    {
        return $this->calendarService->getAttendances();
    }

    /**
     * El admin acepta la evidencia presentada por el practicante.
     * La asistencia pasa a "asistio" y la evidencia queda aprobada.
     */
    public function validarAsistencia(Asistencia $asistencia, Request $request)
    {
        $data = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        $asistencia->tipo = EstadoAsistenciaEnum::ASISTIO;
        $asistencia->save();

        $asistencia->justificaciones()
            ->where('tipo', 'evidencia')
            ->where('estado', 'pendiente')
            ->update([
                'estado' => 'aprobada',
                'observacion' => $data['observacion'] ?? null,
            ]);

        return response()->json([
            'message' => 'La asistencia ha sido validada correctamente.',
        ]);
    }

    /**
     * El admin rechaza la evidencia presentada por el practicante.
     * La asistencia pasa a "falto" y la evidencia queda rechazada.
     */
    public function rechazarAsistencia(Asistencia $asistencia, Request $request)
    {
        $data = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        $asistencia->tipo = EstadoAsistenciaEnum::FALTO;
        $asistencia->save();

        $asistencia->justificaciones()
            ->where('tipo', 'evidencia')
            ->where('estado', 'pendiente')
            ->update([
                'estado' => 'rechazada',
                'observacion' => $data['observacion'] ?? null,
            ]);

        return response()->json([
            'message' => 'La evidencia ha sido rechazada. La asistencia quedó como falta.',
        ]);
    }

    /**
     * El admin acepta la justificación de una falta.
     * La asistencia pasa a "justificado" y la justificación queda aprobada.
     */
    public function validarJustificacion(Asistencia $asistencia, Request $request)
    {
        $data = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        $asistencia->tipo = EstadoAsistenciaEnum::JUSTIFICADO;
        $asistencia->save();

        $asistencia->justificaciones()
            ->where('tipo', 'justificacion')
            ->where('estado', 'pendiente')
            ->update([
                'estado' => 'aprobada',
                'observacion' => $data['observacion'] ?? null,
            ]);

        return response()->json([
            'message' => 'La justificación ha sido aceptada correctamente.',
        ]);
    }

    /**
     * El admin rechaza la justificación de una falta.
     * La asistencia continúa como "falto" y la justificación queda rechazada.
     */
    public function rechazarJustificacion(Asistencia $asistencia, Request $request)
    {
        $data = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        $asistencia->tipo = EstadoAsistenciaEnum::FALTO;
        $asistencia->save();

        $asistencia->justificaciones()
            ->where('tipo', 'justificacion')
            ->where('estado', 'pendiente')
            ->update([
                'estado' => 'rechazada',
                'observacion' => $data['observacion'] ?? null,
            ]);

        return response()->json([
            'message' => 'La justificación ha sido rechazada. La asistencia quedó como falta.',
        ]);
    }
}
