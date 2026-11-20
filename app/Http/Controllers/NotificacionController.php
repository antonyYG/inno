<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificacionDestinoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificacionController extends Controller
{
    public function __construct(
        protected NotificacionDestinoService $destinoService
    ) {}

    public function data(): JsonResponse
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        $formatear = fn (DatabaseNotification $notificacion): array => $this->formatear(
            $notificacion,
            $usuario
        );

        $pendientes = $usuario->notifications()
            ->whereNull('read_at')
            ->latest()
            ->take(10)
            ->get()
            ->map($formatear);

        $historial = $usuario->notifications()
            ->whereNotNull('read_at')
            ->latest()
            ->take(10)
            ->get()
            ->map($formatear);

        return response()->json([
            'contador' => $usuario->unreadNotifications()->count(),
            'notificaciones' => $pendientes,
            'historial' => $historial,
        ]);
    }

    public function marcarLeida(string $id): JsonResponse
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        $notificacion = $usuario->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notificacion->markAsRead();

        return response()->json([
            'message' => 'Notificación marcada como leída.',
            'id' => $id,
            'leida' => true,
            'contador' => $usuario->unreadNotifications()->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatear(DatabaseNotification $notificacion, User $usuario): array
    {
        $destino = $this->destinoService->resolver($notificacion, $usuario);
        $datos = is_array($notificacion->data) ? $notificacion->data : [];
        $creada = $notificacion->created_at->copy();

        return [
            'id' => $notificacion->id,
            'titulo' => $datos['titulo'] ?? 'Notificación',
            'mensaje' => $datos['mensaje'] ?? '',
            'tipo' => $destino['tipo'],
            'etiqueta' => $destino['etiqueta'],
            'icono' => $destino['icono'],
            'url' => $destino['url'],
            'registro_id' => $destino['justificacion_id'] ?? $destino['asistencia_id'],
            'fecha' => $creada->translatedFormat('d M Y'),
            'hora' => $creada->format('H:i'),
            'fecha_relativa' => $creada->diffForHumans(),
            'leida' => $notificacion->read_at !== null,
        ];
    }
}
