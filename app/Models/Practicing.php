<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Practicing extends Model
{
    protected $fillable = [
        'user_id',
        'discord_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
