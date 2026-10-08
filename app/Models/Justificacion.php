<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Justificacion extends Model
{
    protected $fillable = [
        'falta_id',
        'descripcion',
        'archivo_path',
        'estado',
    ];

    public function falta()
    {
        return $this->belongsTo(Falta::class,'falta_id');
    }
}
