<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Exports\EvidenceExport;
use App\Models\User;
use App\Services\AsistenciaService;
use App\Services\ReporteEvidenciaService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function __construct(
        protected AsistenciaService $asistenciaService,
        protected ReporteEvidenciaService $reporteEvidenciaService
    ) {}

    public function index()
    {
        $practicantes = User::role('practicante')
            ->select('id', 'nombre', 'apellidos')
            ->where('estado', 1)
            ->get()
            ->mapWithKeys(fn ($usuario) => [
                $usuario->id => $usuario->nombre.' '.$usuario->apellidos,
            ])
            ->prepend('Todos los practicantes', '');

        return view('admin.reportes.index', compact('practicantes'));
    }

    // REPORTE DE ASISTENCIAS

    public function filtrar(Request $request)
    {
        $asistencias = $this->asistenciaService->getAsistencia($request);

        return response()->json([
            'total' => $asistencias->count(),
            'resumen' => [
                'asistio' => $asistencias->filter(fn ($a) => $this->estadoValor($a->tipo) === 'asistio')->count(),
                'tardanza' => $asistencias->filter(fn ($a) => $this->estadoValor($a->tipo) === 'tardanza')->count(),
                'falto' => $asistencias->filter(fn ($a) => $this->estadoValor($a->tipo) === 'falto')->count(),
                'pendiente' => $asistencias->filter(fn ($a) => $this->estadoValor($a->tipo) === 'pendiente')->count(),
                'justificado' => $asistencias->filter(fn ($a) => $this->estadoValor($a->tipo) === 'justificado')->count(),
            ],
            'data' => $asistencias->map(fn ($asistencia) => [
                'id' => $asistencia->id,
                'practicante' => $asistencia->usuario->nombre.' '.$asistencia->usuario->apellidos,
                'fecha' => $asistencia->fecha,
                'estado' => $this->estadoValor($asistencia->tipo),
            ])->values(),
        ]);
    }

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

        return Excel::download(
            new AttendanceExport($asistencias),
            'reporte-asistencias.xlsx'
        );
    }

    // REPORTE DE EVIDENCIAS

    public function filtrarEvidencias(Request $request)
    {
        $evidencias = $this->reporteEvidenciaService->getEvidencias($request);

        return response()->json([
            'total' => $evidencias->count(),
            'resumen' => [
                'pendiente' => $evidencias->where('estado', 'pendiente')->count(),
                'aprobada' => $evidencias->where('estado', 'aprobada')->count(),
                'rechazada' => $evidencias->where('estado', 'rechazada')->count(),
            ],
            'data' => $evidencias->map(fn ($evidencia) => [
                'id' => $evidencia->id,
                'practicante' => $evidencia->asistencia?->usuario
                    ? $evidencia->asistencia->usuario->nombre.' '.
                      $evidencia->asistencia->usuario->apellidos
                    : 'Sin practicante',
                'fecha' => $evidencia->fecha,
                'descripcion' => $evidencia->descripcion,
                'estado' => $evidencia->estado,
                'observacion' => $evidencia->observacion,
                'archivo' => $evidencia->archivo_path
                    ? Storage::disk('public')->url($evidencia->archivo_path)
                    : null,
                'nombre_archivo' => $evidencia->archivo_path
                    ? basename($evidencia->archivo_path)
                    : null,
            ])->values(),
        ]);
    }

    public function exportarEvidenciasPdf(Request $request)
    {
        $evidencias = $this->reporteEvidenciaService->getEvidencias($request);

        $pdf = Pdf::loadView('admin.reportes.evidencias-pdf', [
            'evidencias' => $evidencias,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte-evidencias.pdf');
    }

    public function exportarEvidenciasExcel(Request $request)
    {
        $evidencias = $this->reporteEvidenciaService->getEvidencias($request);

        return Excel::download(
            new EvidenceExport($evidencias),
            'reporte-evidencias.xlsx'
        );
    }

    private function estadoValor($estado): string
    {
        return $estado instanceof BackedEnum
            ? $estado->value
            : (string) $estado;
    }
}
