<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\PracticingController;
use App\Http\Controllers\UserController;
use App\Models\Attendance;
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
Route::resource('/practicings', PracticingController::class);

Route::get('/calendar-data',function(){
    $attendances = Attendance::with('practicing.user')->get();
    return $attendances->map(function($attendance){
        return [
            'id' => $attendance->id,
            'title' => $attendance->practicing->user->name,
            'start' => $attendance->check_in,
            'end' => $attendance->check_out,
        ];
    });
})->name('calendar.data');
