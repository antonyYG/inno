<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JustificacionController;
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

// Reportes
Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
Route::get('/reportes/filtrar', [ReporteController::class, 'filtrar'])->name('reportes.filtrar');
Route::get('/reportes/exportar/pdf', [ReporteController::class, 'exportarPdf'])->name('reportes.exportar.pdf');
Route::get('/reportes/exportar/excel', [ReporteController::class, 'exportarExcel'])->name('reportes.exportar.excel');

// Calendario
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
Route::get('/justificaciones', [JustificacionController::class, 'index'])->name('justifications.index');
Route::get('/justificaciones/data', [JustificacionController::class, 'data'])->name('justifications.data');
Route::patch('/users/{user}/toggle-status', [UserController::class, 'Status'])->name('users.toggle-status');

Route::get('/tick', [TickController::class, 'index'])->name('tick.index');

// Validar asistencias y faltas
Route::post('/asistencias/{asistencia}', [CalendarController::class, 'validarAsistencia'])->name('asistencias.validar');
Route::post('/asistencias/{asistencia}/justificacion/validar', [CalendarController::class, 'validarJustificacion'])->name('justificaciones.validar');
Route::post('/asistencias/{asistencia}/rechazar', [CalendarController::class, 'rechazarAsistencia'])->name('asistencias.rechazar');
Route::post('/asistencias/{asistencia}/justificacion/rechazar', [CalendarController::class, 'rechazarJustificacion'])->name('justificaciones.rechazar');
Route::post('/tick/marcar', [TickController::class, 'marcar'])
    ->name('tick.marcar');

Route::get('/calendar-data', [CalendarController::class, 'data'])->name('calendar.data');
Route::post('/practicing/justificaciones/{asistencia}/store', [JustificacionController::class, 'justificateAsistencia'])->name('practicing.evidencias.store');
Route::post('/practicing/{asistencia}/justificaciones', [JustificacionController::class, 'justificateFalta'])->name('practicing.justificaciones.store');

// Certificado
Route::get('/certificate/{user}',[UserController::class, 'getCertificate'])->name('certificate.generate');
