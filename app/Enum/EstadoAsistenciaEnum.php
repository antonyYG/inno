<?php

namespace App\Enum;

enum EstadoAsistenciaEnum: string
{
    case PENDIENTE = 'pendiente';
    case ASISTIO = 'asistio';
    case FALTO = 'falto';
    case TARDANZA = 'tardanza';
    case JUSTIFICADO = 'justificado';
}
