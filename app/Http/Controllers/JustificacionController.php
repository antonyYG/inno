<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Justificacion;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class JustificacionController extends Controller
{
    public function index()
    {
        return view('admin.justificaciones.index');
    }

    public function data()
    {
        $query = Justificacion::query()
            ->with('asistencia.usuario')
            ->select(['id', 'asistencia_id', 'fecha', 'tipo', 'estado']);

        return DataTables::eloquent($query)
            ->addColumn('usuario', fn ($justificacion) => trim(
                ($justificacion->asistencia?->usuario?->nombre ?? '').' '.
                ($justificacion->asistencia?->usuario?->apellidos ?? '')
            ) ?: 'Usuario desconocido')
            ->addColumn('acciones', fn ($justificacion) => view(
                'admin.justificaciones.actions',
                compact('justificacion')
            )->render())
            ->rawColumns(['acciones'])
            ->toJson();
    }

    public function justificateAsistencia(Request $request, Asistencia $asistencia)
    {
        // Solo el dueño de la asistencia puede presentar su evidencia.
        if ($asistencia->usuario_id !== auth()->id()) {
            abort(403);
        }

        // No se permite otra evidencia si ya hay una pendiente o aprobada.
        $yaEnviada = $asistencia->justificaciones()
            ->where('tipo', 'evidencia')
            ->whereIn('estado', ['pendiente', 'aprobada'])
            ->exists();

        if ($yaEnviada) {
            return back()->with(
                'error',
                'Ya enviaste tu evidencia de esta jornada, espera su validación.'
            );
        }

        $data = $request->validate([
            'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'descripcion' => 'required|string|max:500',
        ]);

        $archivoPath = $request->file('archivo')->store('evidencias', 'public');

        $asistencia->justificaciones()->create([
            'fecha' => now(),
            'archivo_path' => $archivoPath,
            'tipo' => 'evidencia',
            'estado' => 'pendiente',
            'descripcion' => $data['descripcion'],
        ]);

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Evidencia enviada',
            'text' => 'Tu evidencia fue enviada, espera su validación.',
        ]);

        return redirect()->back();
    }

    public function justificateFalta(Request $request, Asistencia $asistencia)
    {
        // Solo el dueño de la asistencia puede justificar su falta.
        if ($asistencia->usuario_id !== auth()->id()) {
            abort(403);
        }

        // Si la falta proviene de una evidencia rechazada, es definitiva: no admite justificación.
        $evidenciaRechazada = $asistencia->justificaciones()
            ->where('tipo', 'evidencia')
            ->where('estado', 'rechazada')
            ->exists();

        if ($evidenciaRechazada) {
            return back()->with(
                'error',
                'Tu asistencia fue rechazada y se considera como falta, no admite justificación.'
            );
        }

        // No se permite otra justificación si ya hay una pendiente o aprobada.
        $yaEnviada = $asistencia->justificaciones()
            ->where('tipo', 'justificacion')
            ->whereIn('estado', ['pendiente', 'aprobada'])
            ->exists();

        if ($yaEnviada) {
            return back()->with(
                'error',
                'Ya enviaste tu justificación, espera su revisión.'
            );
        }

        $data = $request->validate([
            'evidencia' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'motivo' => 'required|string|max:1000',
        ]);

        $archivoPath = $request->file('evidencia')->store('justificaciones', 'public');

        $asistencia->justificaciones()->create([
            'fecha' => now(),
            'archivo_path' => $archivoPath,
            'tipo' => 'justificacion',
            'estado' => 'pendiente',
            'descripcion' => $data['motivo'],
        ]);

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Justificación enviada',
            'text' => 'Tu justificación fue enviada, espera su revisión.',
        ]);

        return redirect()->back();
    }
}
