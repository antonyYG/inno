<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'practicante_id',
        'fecha',
        'turno',
        'hora_ingreso',
        'hora_fin',
        'tiempo_trabajado'
    ];

    public function practicante()
    {
        return $this->belongsTo(Practicante::class,'practicante_id');
    }

}
