<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Asistencia;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    public function __construct(
        protected DashboardService $dashboardService
    ){}

    public function index()
    {
        if (auth()->user()->hasRole('admin')) {
           $dataDahboard = $this->dashboardService->getDashboardAdmin();
           return view('admin.dashboard',compact('dataDahboard'));
        }
        $dataDahboard = $this->dashboardService->getDashboardPracticante();

        return view('practicing.dashboard',compact('dataDahboard'));

        return $dataDahboard;

    }
}
