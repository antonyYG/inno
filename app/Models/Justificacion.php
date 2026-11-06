<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Justificacion extends Model
{
    protected $table = 'justificaciones';

    protected $fillable = [
        'asistencia_id',
        'archivo_path',
        'fecha',
        'tipo',
        'descripcion',
    ];

    public function asistencia()
    {
        return $this->belongsTo(Asistencia::class, 'asistencia_id');
    }

}
