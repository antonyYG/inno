<?php

namespace App\Models;

use App\Enum\EstadoAsistenciaEnum;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $fillable = [
        'usuario_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'tipo',
        'tiempo_trabajado',
    ];

    protected $casts = [
        'tipo' => EstadoAsistenciaEnum::class,

    ];

    public function usuario()
    {
        return $this->belongsTo(User::class,'usuario_id');
    }

    public function justificaciones()
    {
        return $this->hasMany(Justificacion::class,'asistencia_id');
    }

}
