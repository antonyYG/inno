<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Justificacion;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class AuditoriaController extends Controller
{
    public function index()
    {
        return view('admin.auditorias.index');
    }

    public function data()
    {
        $query = Activity::with(['causer', 'subject'])
            ->where('log_name', 'auditoria')
            ->latest();

        return DataTables::eloquent($query)
            ->addColumn('administrador', fn ($a) => $a->causer?->nombre ?? 'No identificado')
            ->addColumn('accion', fn ($a) => $a->event)
            ->addColumn('practicante', function ($a) {

                $usuario = null;

                if ($a->subject instanceof Asistencia) {
                    $usuario = $a->subject->usuario;
                }

                if ($a->subject instanceof Justificacion) {
                    $usuario = $a->subject->asistencia?->usuario;
                }

                return $usuario
                    ? $usuario->nombre.' '.$usuario->apellidos
                    : 'No disponible';
            })
            ->addColumn('estado_anterior', fn ($a) => $a->properties['datos_anteriores']['tipo'] ?? '-')
            ->addColumn('estado_nuevo', fn ($a) => $a->properties['datos_nuevos']['tipo'] ?? '-')
            ->toJson();
    }
}
