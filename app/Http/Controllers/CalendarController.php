<?php

namespace App\Http\Controllers;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Services\AuditoriaJustificacionService;
use App\Services\AuditoriaService;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService,
        protected AuditoriaService $auditoriaService,
        protected AuditoriaJustificacionService $auditoriaJustificacionService
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
     * Validar evidencia del practicante.
     */
    public function validarAsistencia(Asistencia $asistencia, Request $request)
    {
        $data = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $tipoAnterior = $this->obtenerEstado($asistencia->tipo);

            // Aprobar la evidencia valida la jornada, pero una tardanza
            // debe conservarse: no se convierte en asistio.
            $esTardanza = $asistencia->tipo === EstadoAsistenciaEnum::TARDANZA;
            $tipoNuevo = $esTardanza ? EstadoAsistenciaEnum::TARDANZA : EstadoAsistenciaEnum::ASISTIO;

            // Actualizar asistencia
            $asistencia->tipo = $tipoNuevo;
            $asistencia->save();

            // Actualizar evidencia sin generar otra auditoría
            $this->actualizarJustificaciones(
                $asistencia,
                'evidencia',
                'aprobada',
                $data['observacion'] ?? null
            );

            // Registrar una sola auditoría
            $this->auditoriaService->registrar(
                $asistencia,
                'evidencia_validada',
                $esTardanza
                    ? 'El administrador validó la evidencia. La tardanza se mantiene registrada.'
                    : 'El administrador validó la asistencia del practicante.',
                ['tipo' => $tipoAnterior],
                ['tipo' => $tipoNuevo->value]
            );

            DB::commit();

            return response()->json([
                'message' => $esTardanza
                    ? 'La evidencia ha sido validada. La tardanza se mantiene registrada.'
                    : 'La asistencia ha sido validada correctamente.',
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            report($e);

            return response()->json([
                'message' => 'Ocurrió un error al validar la asistencia.',
            ], 500);
        }
    }

    /**
     * Rechazar evidencia del practicante.
     */
    public function rechazarAsistencia(Asistencia $asistencia, Request $request)
    {
        $data = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $tipoAnterior = $this->obtenerEstado($asistencia->tipo);

            // Actualizar asistencia
            $asistencia->tipo = EstadoAsistenciaEnum::FALTO;
            $asistencia->save();

            // Actualizar evidencia sin generar otra auditoría
            $this->actualizarJustificaciones(
                $asistencia,
                'evidencia',
                'rechazada',
                $data['observacion'] ?? null
            );

            // Registrar una sola auditoría
            $this->auditoriaService->registrar(
                $asistencia,
                'evidencia_rechazada',
                'El administrador rechazó la evidencia y registró una falta.',
                ['tipo' => $tipoAnterior],
                ['tipo' => EstadoAsistenciaEnum::FALTO->value]
            );

            DB::commit();

            return response()->json([
                'message' => 'La evidencia ha sido rechazada. La asistencia quedó como falta.',
            ]);

        } catch (Throwable $e) {
            DB::rollBack();

            report($e);

            return response()->json([
                'message' => 'Ocurrió un error al rechazar la evidencia.',
            ], 500);
        }
    }

    /**
     * Aprobar justificación de una falta.
     */
    public function validarJustificacion(Asistencia $asistencia, Request $request)
    {
        $data = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        try {
            $esTardanza = $asistencia->tipo === EstadoAsistenciaEnum::TARDANZA;

            $this->auditoriaJustificacionService->registrarAceptacion(
                $asistencia,
                $data['observacion'] ?? null
            );

            return response()->json([
                'message' => $esTardanza
                    ? 'La justificación ha sido aceptada. La tardanza se mantiene registrada.'
                    : 'La justificación ha sido aceptada correctamente.',
            ]);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Ocurrió un error al aprobar la justificación.',
            ], 500);
        }
    }

    /**
     * Rechazar justificación de una falta.
     */
    public function rechazarJustificacion(Asistencia $asistencia, Request $request)
    {
        $data = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        try {
            $esTardanza = $asistencia->tipo === EstadoAsistenciaEnum::TARDANZA;

            $this->auditoriaJustificacionService->registrarRechazo(
                $asistencia,
                $data['observacion'] ?? null
            );

            return response()->json([
                'message' => $esTardanza
                    ? 'La justificación ha sido rechazada. La tardanza se mantiene registrada.'
                    : 'La justificación ha sido rechazada. La asistencia quedó como falta.',
            ]);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Ocurrió un error al rechazar la justificación.',
            ], 500);
        }
    }

    /**
     * Actualizar evidencias pendientes.
     * La auditoría de evidencias se mantiene en su flujo original.
     */
    private function actualizarJustificaciones(
        Asistencia $asistencia,
        string $tipo,
        string $nuevoEstado,
        ?string $observacion
    ): void {
        $justificaciones = $asistencia->justificaciones()
            ->where('tipo', $tipo)
            ->where('estado', 'pendiente')
            ->get();

        foreach ($justificaciones as $justificacion) {
            $justificacion->estado = $nuevoEstado;
            $justificacion->observacion = $observacion;
            $justificacion->save();
        }
    }

    /**
     * Obtener el valor del enum o del estado.
     */
    private function obtenerEstado($estado): ?string
    {
        return $estado instanceof EstadoAsistenciaEnum
            ? $estado->value
            : $estado;
    }
}
