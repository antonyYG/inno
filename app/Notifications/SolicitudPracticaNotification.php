<?php

namespace App\Notifications;

use App\Models\Justificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SolicitudPracticaNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Justificacion $justificacion
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
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', url('/'))
    //         ->line('Thank you for using our application!');
    // }

    public function toDatabase(object $notifiable): array
    {
        $justificacion = $this->justificacion;
        $usuario = $justificacion->asistencia->usuario;
        $esEvidencia = $justificacion->tipo === 'evidencia';

        return [
            'titulo' => $esEvidencia
                ? 'Nueva evidencia de asistencia'
                : 'Nueva justificación de falta',

            'mensaje' => $usuario->nombre.' '.
                $usuario->apellidos.
                ($esEvidencia
                    ? ' envió una evidencia para validar su asistencia.'
                    : ' presentó una justificación de falta.'),

            'tipo' => $justificacion->tipo,

            'justificacion_id' => $justificacion->id,

            'asistencia_id' => $justificacion->asistencia_id,

            'practicante' => $usuario->nombre.' '.$usuario->apellidos,

            'estado' => $justificacion->estado,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
