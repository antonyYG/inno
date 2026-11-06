<?php

namespace App\Http\Controllers;

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Models\Evidencia;
use App\Models\Justificacion;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class JustificacionController extends Controller
{
    public function justificateAsistencia(Request $request, Asistencia $asistencia)
    {
        $data = $request->validate([
            'archivo' => 'required|file|mimes:png,jpg',
        ]);

        $archivoPath = $request->file('archivo')->store('evidencias', 'public');
        $fecha = now();
        $data['fecha'] = $fecha;
        $data['asistencia_id'] = $asistencia->id;
        $data['archivo_path'] = $archivoPath;
        $data['tipo'] = 'evidencia';
        $data['descripcion'] = 'Avance de trabajo';

        $asistencia->tipo = EstadoAsistenciaEnum::ASISTIO;
        $asistencia->save();
        Justificacion::create($data);

        session()->flash('swal',[
            'icon' => 'success',
            'title' => 'Justificación enviada',
            'text' => 'La justificación ha sido enviada correctamente.',
        ]);
        return redirect()->back();

    }

    public function justificateFalta(Request $request, Asistencia $asistencia)
    {
        // dd($request->toArray());
        $data = $request->validate([
            'evidencia' => 'required|file|mimes:png,jpg',
        ]);

        $archivoPath = $request->file('evidencia')->store('evidencias', 'public');
        $fecha = now();
        $data['fecha'] = $fecha;
        $data['asistencia_id'] = $asistencia->id;
        $data['archivo_path'] = $archivoPath;
        $data['tipo'] = 'evidencia';
        $data['descripcion'] = 'Avance de trabajo';

        $asistencia->tipo = EstadoAsistenciaEnum::JUSTIFICADO;
        $asistencia->save();
        Justificacion::create($data);

        session()->flash('swal',[
            'icon' => 'success',
            'title' => 'Justificación enviada',
            'text' => 'La justificación ha sido enviada correctamente.',
        ]);
        return redirect()->back();
    }

}
