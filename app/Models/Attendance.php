<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'practicing_id',
        'date',
        'check_in',
        'check_out',
        'last_join',
        'last_leave',
        'worked_minutes',
        'shift',
        'status'
    ];

    public function practicing()
    {
        return $this->belongsTo(Practicing::class);
    }
}
