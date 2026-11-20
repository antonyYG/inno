<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Exports\EvidenceExport;
use App\Exports\ReporteAuditoriaExport;
use App\Http\Requests\FiltroReporteRequest;
use App\Services\ReporteAsistenciaService;
use App\Services\ReporteAuditoriaService;
use App\Services\ReporteEvidenciaService;
use App\Services\ReporteFiltroService;
use App\Services\ReporteServiceContract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    public function __construct(
        protected ReporteFiltroService $filtroService,
        protected ReporteAsistenciaService $asistenciaService,
        protected ReporteEvidenciaService $evidenciaService,
        protected ReporteAuditoriaService $auditoriaService
    ) {}

    public function index(): View
    {
        return $this->vista('asistencias');
    }

    public function asistencia(): View
    {
        return $this->vista('asistencias');
    }

    public function evidencias(): View
    {
        return $this->vista('evidencias');
    }

    public function auditoria(): View
    {
        return $this->vista('auditorias');
    }

    public function filtrarAsistencias(FiltroReporteRequest $request): JsonResponse
    {
        return $this->responder($this->asistenciaService, $request);
    }

    public function filtrarEvidencias(FiltroReporteRequest $request): JsonResponse
    {
        return $this->responder($this->evidenciaService, $request);
    }

    public function filtrarAuditoria(FiltroReporteRequest $request): JsonResponse
    {
        return $this->responder($this->auditoriaService, $request);
    }

    public function exportarAsistenciaPdf(FiltroReporteRequest $request): Response
    {
        return $this->exportarPdf(
            $request,
            $this->asistenciaService,
            'admin.reportes.pdf',
            'reporte-asistencias.pdf'
        );
    }

    public function exportarAsistenciaExcel(FiltroReporteRequest $request): Response
    {
        return $this->exportarExcel(
            $request,
            $this->asistenciaService,
            AttendanceExport::class,
            'reporte-asistencias.xlsx'
        );
    }

    public function exportarEvidenciasPdf(FiltroReporteRequest $request): Response
    {
        return $this->exportarPdf(
            $request,
            $this->evidenciaService,
            'admin.reportes.evidencias-pdf',
            'reporte-evidencias.pdf'
        );
    }

    public function exportarEvidenciasExcel(FiltroReporteRequest $request): Response
    {
        return $this->exportarExcel(
            $request,
            $this->evidenciaService,
            EvidenceExport::class,
            'reporte-evidencias.xlsx'
        );
    }

    public function exportarAuditoriaPdf(FiltroReporteRequest $request): Response
    {
        return $this->exportarPdf(
            $request,
            $this->auditoriaService,
            'admin.reportes.auditoria-pdf',
            'auditoria-general.pdf'
        );
    }

    public function exportarAuditoriaExcel(FiltroReporteRequest $request): Response
    {
        return $this->exportarExcel(
            $request,
            $this->auditoriaService,
            ReporteAuditoriaExport::class,
            'auditoria-general.xlsx'
        );
    }

    private function vista(string $tipoReporte): View
    {
        return view('admin.reportes.index', [
            ...$this->filtroService->catalogos(),
            'tipoReporte' => $tipoReporte,
        ]);
    }

    private function responder(ReporteServiceContract $servicio, FiltroReporteRequest $request): JsonResponse
    {
        $filtros = $this->filtroService->normalizar($request->validated());
        $paginador = $servicio->paginar($filtros, $request->integer('per_page', 15));

        return response()->json([
            'total' => $paginador->total(),
            'resumen' => $servicio->resumen($filtros),
            'data' => $paginador->items(),
            'pagination' => $this->datosPaginacion($paginador),
        ]);
    }

    private function exportarPdf(
        FiltroReporteRequest $request,
        ReporteServiceContract $servicio,
        string $vista,
        string $archivo
    ): Response {
        $registros = $servicio->obtenerTodos(
            $this->filtroService->normalizar($request->validated())
        );

        return Pdf::loadView($vista, ['registros' => $registros])
            ->setPaper('a4', 'landscape')
            ->download($archivo);
    }

    /**
     * @param  class-string  $exportador
     */
    private function exportarExcel(
        FiltroReporteRequest $request,
        ReporteServiceContract $servicio,
        string $exportador,
        string $archivo
    ): Response {
        $registros = $servicio->obtenerTodos(
            $this->filtroService->normalizar($request->validated())
        );

        return Excel::download(new $exportador($registros), $archivo);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosPaginacion(LengthAwarePaginator $paginador): array
    {
        $enlaces = [];

        for ($pagina = 1; $pagina <= $paginador->lastPage(); $pagina++) {
            $enlaces[] = [
                'page' => $pagina,
                'url' => $paginador->url($pagina),
                'active' => $pagina === $paginador->currentPage(),
            ];
        }

        return [
            'current_page' => $paginador->currentPage(),
            'last_page' => $paginador->lastPage(),
            'per_page' => $paginador->perPage(),
            'total' => $paginador->total(),
            'from' => $paginador->firstItem(),
            'to' => $paginador->lastItem(),
            'prev_page_url' => $paginador->previousPageUrl(),
            'next_page_url' => $paginador->nextPageUrl(),
            'links' => $enlaces,
        ];
    }
}
