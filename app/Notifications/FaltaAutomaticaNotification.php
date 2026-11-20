<?php

namespace App\Notifications;

use App\Models\Asistencia;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FaltaAutomaticaNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public array $asistencias,
        public string $fecha,
        public ?string $fechaCalendario = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $nombres = collect($this->asistencias)
            ->map(fn (Asistencia $asistencia) => trim(
                ($asistencia->usuario?->nombre ?? '').' '.
                ($asistencia->usuario?->apellidos ?? '')
            ) ?: 'Practicante')
            ->implode(', ');

        return [
            'titulo' => 'Faltas registradas automáticamente',
            'mensaje' => "El {$this->fecha} no registraron asistencia: {$nombres}.",
            'fecha' => $this->fecha,
            'fecha_calendario' => $this->fechaCalendario,
            'cantidad' => count($this->asistencias),
            'asistencia_ids' => array_values(array_map(
                fn (Asistencia $asistencia): int|string => $asistencia->id,
                $this->asistencias
            )),
        ];
    }
}
