<?php

namespace App\Enum;

enum EstadoAsistenciaEnum: string
{
    case ASISTIO = 'asistio';
    case FALTO = 'falto';
    case TARDANZA = 'tardanza';
}
