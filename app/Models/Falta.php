<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Falta extends Model
{
    protected $fillable = [
        'practicing_id',
        'date',
        'shift',
    ];

    public function practicing()
    {
        return $this->belongsTo(Practicing::class);
    }

}
