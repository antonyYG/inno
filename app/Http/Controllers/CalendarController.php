<?php

namespace App\Http\Controllers;

use App\Services\CalendarService;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    )
    {}

    public function index()
    {
        if (auth()->user()->hasRole('practicante')) {
            return view('practicing.calendar.index');
        }
        return view('admin.calendar.index');
    }

    public function data()
    {
        return $this->calendarService->getAttendances();
    }

}
