<?php

namespace App\Http\Controllers;

class NotificacionController extends Controller
{
    public function data()
    {
        $usuario = auth()->user();

        $notificaciones = $usuario->notifications()
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($notificacion) => [
                'id' => $notificacion->id,
                'titulo' => $notificacion->data['titulo'] ?? 'Notificación',
                'mensaje' => $notificacion->data['mensaje'] ?? '',
                'fecha' => $notificacion->created_at->diffForHumans(),
                'leida' => $notificacion->read_at !== null,
            ]);

        return response()->json([
            'contador' => $usuario->unreadNotifications()->count(),
            'notificaciones' => $notificaciones,
        ]);
    }

    public function marcarLeida(string $id)
    {
        $notificacion = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notificacion->markAsRead();

        return response()->json([
            'message' => 'Notificación marcada como leída.',
        ]);
    }
}
