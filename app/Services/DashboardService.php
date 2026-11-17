<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Asistencia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardAdmin()
    {
        $pracActivos = User::role('practicante')
            ->where('estado', true)
            ->count();

        $today = Asistencia::whereDate('fecha', today('America/Lima'));

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
            today('America/Lima')->startOfWeek(),
            today('America/Lima')->endOfWeek(),
        ])
            ->selectRaw('fecha, tipo, COUNT(*) as total')
            ->groupBy('fecha', 'tipo')
            ->get();

        $areas = Area::select('id', 'nombre')
            ->withCount('users')
            ->get();

        // Seguimiento de todos los practicantes
        $practicantes = User::role('practicante')
            ->select([
                'id',
                'nombre',
                'apellidos',
                'email',
                'fecha_inicio',
                'fecha_fin',
                'horas_requeridas',
                'estado',
            ])
            ->get();

        // Obtener las horas acumuladas de todos los practicantes
        $horasPorUsuario = Asistencia::query()
            ->select('usuario_id')
            ->selectRaw('COALESCE(SUM(TIME_TO_SEC(tiempo_trabajado)), 0) as segundos')
            ->whereIn('tipo', ['asistio', 'tardanza'])
            ->whereNotNull('tiempo_trabajado')
            ->groupBy('usuario_id')
            ->pluck('segundos', 'usuario_id');

        $seguimientoPracticantes = $practicantes->map(
            fn ($practicante) => $this->calcularSeguimiento(
                $practicante,
                (int) ($horasPorUsuario[$practicante->id] ?? 0)
            )
        );

        return [
            'pracActivos' => $pracActivos,
            'tarToday' => $tarToday,
            'falToday' => $falToday,
            'attendaceToday' => $attendaceToday,
            'attendanceWeek' => $attendanceWeek,
            'areas' => $areas,
            'seguimientoPracticantes' => $seguimientoPracticantes,
        ];
    }

    public function getDashboardPracticante()
    {
        $usuario = User::findOrFail(auth()->id());

        $query = Asistencia::where('usuario_id', $usuario->id)
            ->whereMonth('fecha', today('America/Lima')->month)
            ->whereYear('fecha', today('America/Lima')->year);

        $assitenciaMes = (clone $query)
            ->where('tipo', 'asistio')
            ->count();

        $tardanzasMes = (clone $query)
            ->where('tipo', 'tardanza')
            ->count();

        $faltasMes = (clone $query)
            ->where('tipo', 'falto')
            ->count();

        $asistenciToday = (clone $query)
            ->whereDate('fecha', today('America/Lima'))
            ->first();

        $asistenciaSemana = Asistencia::where('usuario_id', $usuario->id)
            ->whereBetween('fecha', [
                today('America/Lima')->startOfWeek(),
                today('America/Lima')->endOfWeek(),
            ])
            ->selectRaw('fecha, tipo, COUNT(*) as total')
            ->groupBy('fecha', 'tipo')
            ->get();

        // Seguimiento del practicante autenticado
        $segundos = (int) Asistencia::where('usuario_id', $usuario->id)
            ->whereIn('tipo', ['asistio', 'tardanza'])
            ->whereNotNull('tiempo_trabajado')
            ->sum(DB::raw('TIME_TO_SEC(tiempo_trabajado)'));

        $seguimiento = $this->calcularSeguimiento($usuario, $segundos);

        return [
            'assitenciaMes' => $assitenciaMes,
            'tardanzasMes' => $tardanzasMes,
            'faltasMes' => $faltasMes,
            'asistenciToday' => $asistenciToday,
            'asistenciaSemana' => $asistenciaSemana,
            'seguimiento' => $seguimiento,
        ];
    }

    /**
     * Calcula el avance de horas de un practicante.
     */
    private function calcularSeguimiento(User $usuario, int $segundos): array
    {
        $hoy = Carbon::today('America/Lima');

        $horasRequeridas = (int) ($usuario->horas_requeridas ?: 360);

        $segundosRequeridos = $horasRequeridas * 3600;

        $segundosPendientes = max(
            0,
            $segundosRequeridos - $segundos
        );

        $porcentaje = $segundosRequeridos > 0
            ? min(100, round(($segundos / $segundosRequeridos) * 100, 2))
            : 0;

        if ($segundos >= $segundosRequeridos) {
            $estado = 'completado';
        } elseif (
            $usuario->fecha_fin &&
            $hoy->gt(Carbon::parse($usuario->fecha_fin))
        ) {
            $estado = 'vencido';
        } else {
            $estado = 'en_curso';
        }

        return [
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'apellidos' => $usuario->apellidos,
            'email' => $usuario->email,

            'fecha_inicio' => $usuario->fecha_inicio
                ? Carbon::parse($usuario->fecha_inicio)->format('d/m/Y')
                : null,

            'fecha_fin' => $usuario->fecha_fin
                ? Carbon::parse($usuario->fecha_fin)->format('d/m/Y')
                : null,

            'horas_requeridas' => $horasRequeridas,

            'horas_acumuladas' => $this->formatearTiempo($segundos),

            'horas_pendientes' => $this->formatearTiempo(
                $segundosPendientes
            ),

            'segundos_acumulados' => $segundos,

            'porcentaje' => $porcentaje,

            'estado_seguimiento' => $estado,
        ];
    }

    /**
     * Convierte segundos a formato HH:MM.
     */
    private function formatearTiempo(int $segundos): string
    {
        $horas = intdiv($segundos, 3600);
        $minutos = intdiv($segundos % 3600, 60);

        return sprintf('%02d:%02d', $horas, $minutos);
    }
}
