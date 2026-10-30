<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\JustificacionController;
use App\Http\Controllers\PracticingController;
use App\Http\Controllers\TickController;
use App\Http\Controllers\UserController;
use App\Models\Asistencia;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('admin.dashboard');
// })->name('dashboard');
Route::get('/', function () {
    return view('admin.dashboard');
})->name('dashboard');
Route::get('/users/data',[UserController::class,'data'])->name('users.data');
Route::resource('/users',UserController::class);
Route::get('/areas/data',[AreaController::class,'data'])->name('areas.data');
Route::resource('/areas',AreaController::class);
Route::get('/calendar',[CalendarController::class,'index'])->name('calendar.index');
Route::resource('/practicings', PracticingController::class);
Route::get('/justificaciones',[JustificacionController::class,'index'])->name('justifications.index');
Route::get('/justificaciones/data',[JustificacionController::class,'data'])->name('justifications.data');
Route::patch('/users/{user}/toggle-status',[UserController::class,'Status'])->name('users.toggle-status');

Route::get('/tick',[TickController::class,'index'])->name('tick.index');

Route::get('/calendar-data',[CalendarController::class,'data'])->name('calendar.data');
