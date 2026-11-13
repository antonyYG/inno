<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\User;
use App\Services\AsistenciaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function __construct(
        protected AsistenciaService $asistenciaService
    ) {}

    public function index()
    {
        $practicantes = User::role('practicante')
            ->select('id', 'nombre', 'apellidos')
            ->where('estado',1)
            ->get()
            ->mapWithKeys(fn ($usuario) => [
                $usuario->id => $usuario->nombre.' '.$usuario->apellidos,
            ])
            ->prepend('Todos los practicantes', '');

        return view('admin.reportes.index', compact('practicantes'));
    }

    /**
     * Filtrar asistencias mediante AJAX.
     */
    public function filtrar(Request $request)
    {
        $asistencias = $this->asistenciaService->getAsistencia($request);

        return response()->json([
            'total' => $asistencias->count(),
            'data' => $asistencias->map(fn ($asistencia) => [
                'practicante' => $asistencia->usuario->nombre.' '.$asistencia->usuario->apellidos,
                'fecha' => $asistencia->fecha,
                'estado' => $asistencia->tipo,
            ]),
        ]);
    }

    /**
     * Exportar reporte PDF.
     */
    public function exportarPdf(Request $request)
    {
        $asistencias = $this->asistenciaService->getAsistencia($request);

        $pdf = Pdf::loadView('admin.reportes.pdf', [
            'asistencias' => $asistencias,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte-asistencias.pdf');
    }

    public function exportarExcel(Request $request)
    {
        $asistencias = $this->asistenciaService->getAsistencia($request);

        return Excel::download(new AttendanceExport($asistencias), 'reporteAsistencia.xlsx');
    }
}
