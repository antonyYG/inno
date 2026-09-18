<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    protected $fillable = [
        'name',
        'last_name',
        'phone',
        'area_id',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

}
