<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('admin.dashboard');
// })->name('dashboard');
Route::get('/', function () {
    return view('admin.dashboard');
})->name('dashboard');
Route::resource('/users',UserController::class);
Route::resource('/areas',AreaController::class);
Route::get('/calendar',[CalendarController::class,'index'])->name('calendar.index');
