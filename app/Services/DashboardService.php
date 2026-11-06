<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Asistencia;
use App\Models\User;

class DashboardService
{
    public function getDashboardAdmin()
    {
        $pracActivos = User::role('practicante')
            ->where('estado', 1)->count();

        $today = Asistencia::where('fecha', today());

        $attendaceToday = (clone $today)
            ->where('tipo', 'asistio')
            ->count();

        $tarToday = (clone $today)
            ->where('tipo', 'tardanza')
            ->count();

        $falToday = (clone $today)
            ->where('tipo', 'falto')
            ->count();

        $attendanceWeek = Asistencia::whereBetween('fecha', [
            today()->startOfWeek(),
            today()->endOfWeek()
        ])
            ->selectRaw('fecha, tipo, COUNT(*) as total')
            ->groupBy('fecha', 'tipo')
            ->get();

        $areas = Area::select('id','nombre')->withCount('users')->get();

        return [
            'pracActivos' => $pracActivos,
            'tarToday' => $tarToday,
            'falToday' => $falToday,
            'attendaceToday' => $attendaceToday,
            'attendanceWeek' => $attendanceWeek,
            'areas' => $areas
        ];

    }

    public function getDashboardPracticante()
    {
        $query = Asistencia::where('usuario_id', auth()->id())
            ->whereMonth('fecha', today()->month);

        $assitenciaMes= (clone $query)
            ->where('tipo','asistio')
            ->count();

        $tardanzasMes = (clone $query)
            ->where('tipo','tardanza')
            ->count();

        $faltasMes = (clone $query)
            ->where('tipo','falto')
            ->count();

        $asistenciToday = (clone $query)
            ->where('fecha', today())
            ->first();

        $asistenciaSemana = Asistencia::where('usuario_id', auth()->id())
            ->whereBetween('fecha', [
                today()->startOfWeek(),
                today()->endOfWeek()
            ])->selectRaw('fecha, tipo, COUNT(*) as total')
            ->groupBy('fecha', 'tipo')
            ->get();;

        return [
            'assitenciaMes' => $assitenciaMes,
            'tardanzasMes' => $tardanzasMes,
            'faltasMes' => $faltasMes,
            'asistenciToday' => $asistenciToday,
            'asistenciaSemana' => $asistenciaSemana
        ];

    }

}
