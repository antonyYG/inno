<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JustificacionController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TickController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('admin.dashboard');
// })->name('dashboard');
// Users
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/users/data', [UserController::class, 'data'])->name('users.data');
Route::resource('/users', UserController::class);

// Areas
Route::get('/areas/data', [AreaController::class, 'data'])->name('areas.data');
Route::resource('/areas', AreaController::class);

// Reportes (solo administradores)
Route::middleware('role:admin')->group(function () {
    Route::get('/reportes', [ReporteController::class, 'index'])
        ->name('reportes.index');

    // Vistas canónicas de cada reporte
    Route::get('/reportes/asistencia', [ReporteController::class, 'asistencia'])
        ->name('reportes.asistencia');

    Route::get('/reportes/evidencias', [ReporteController::class, 'evidencias'])
        ->name('reportes.evidencias');

    Route::get('/reportes/auditoria', [ReporteController::class, 'auditoria'])
        ->name('reportes.auditoria');

    // Reporte de asistencias
    Route::get('/reportes/filtrar', [ReporteController::class, 'filtrarAsistencias'])
        ->name('reportes.filtrar');

    Route::get('/reportes/exportar/pdf', [ReporteController::class, 'exportarAsistenciaPdf'])
        ->name('reportes.exportar.pdf');

    Route::get('/reportes/exportar/excel', [ReporteController::class, 'exportarAsistenciaExcel'])
        ->name('reportes.exportar.excel');

    // Reporte de evidencias
    Route::get('/reportes/filtrar-evidencias', [ReporteController::class, 'filtrarEvidencias'])
        ->name('reportes.filtrar.evidencias');

    Route::get('/reportes/exportar-evidencias/pdf', [ReporteController::class, 'exportarEvidenciasPdf'])
        ->name('reportes.exportar.evidencias.pdf');

    Route::get('/reportes/exportar-evidencias/excel', [ReporteController::class, 'exportarEvidenciasExcel'])
        ->name('reportes.exportar.evidencias.excel');

    // Auditoría general (activity_log)
    Route::get('/reportes/filtrar-auditorias', [ReporteController::class, 'filtrarAuditoria'])
        ->name('reportes.filtrar.auditorias');

    Route::get('/reportes/exportar-auditorias/pdf', [ReporteController::class, 'exportarAuditoriaPdf'])
        ->name('reportes.exportar.auditorias.pdf');

    Route::get('/reportes/exportar-auditorias/excel', [ReporteController::class, 'exportarAuditoriaExcel'])
        ->name('reportes.exportar.auditorias.excel');
});
// Calendario
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
Route::get('/justificaciones', [JustificacionController::class, 'index'])->name('justifications.index');
Route::get('/justificaciones/data', [JustificacionController::class, 'data'])->name('justifications.data');
Route::patch('/users/{user}/toggle-status', [UserController::class, 'Status'])->name('users.toggle-status');

Route::get('/tick', [TickController::class, 'index'])->name('tick.index');

// Validar asistencias y faltas
Route::post('/asistencias/{asistencia}', [CalendarController::class, 'validarAsistencia'])->name('asistencias.validar');
Route::post('/asistencias/{asistencia}/rechazar', [CalendarController::class, 'rechazarAsistencia'])->name('asistencias.rechazar');

Route::middleware('role:admin')->group(function () {
    Route::post('/asistencias/{asistencia}/justificacion/validar', [CalendarController::class, 'validarJustificacion'])->name('justificaciones.validar');
    Route::post('/asistencias/{asistencia}/justificacion/rechazar', [CalendarController::class, 'rechazarJustificacion'])->name('justificaciones.rechazar');
});
Route::post('/tick/marcar', [TickController::class, 'marcar'])
    ->name('tick.marcar');

Route::get('/calendar-data', [CalendarController::class, 'data'])->name('calendar.data');
Route::post('/practicing/justificaciones/{asistencia}/store', [JustificacionController::class, 'justificateAsistencia'])->name('practicing.evidencias.store');
Route::post('/practicing/{asistencia}/justificaciones', [JustificacionController::class, 'justificateFalta'])->name('practicing.justificaciones.store');

// Certificado
Route::get('/certificate/{user}', [UserController::class, 'getCertificate'])->name('certificate.generate');
Route::get('/mi-certificado', [UserController::class, 'miCertificado'])->name('certificate.mine');

// auditorias (solo administradores)
Route::middleware('role:admin')->group(function () {
    Route::get('/auditorias', [AuditoriaController::class, 'index'])->name('auditorias.index');
    Route::get('/auditorias/data', [AuditoriaController::class, 'data'])->name('auditorias.data');
});

// Notificaciones

Route::get('/notificaciones', [NotificacionController::class, 'data'])
    ->name('notificaciones.data');

Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida'])
    ->name('notificaciones.read');
