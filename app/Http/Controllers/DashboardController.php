<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index()
    {
        if (auth()->user()->hasRole('admin')) {
            $dataDahboard = $this->dashboardService->getDashboardAdmin();

            return view('admin.dashboard', compact('dataDahboard'));
        }
        $dataDahboard = $this->dashboardService->getDashboardPracticante();

        return view('practicing.dashboard', compact('dataDahboard'));

    }
}
