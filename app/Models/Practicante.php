<?php

namespace App\Models;

use App\Models\Scopes\FiltersScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([
    FiltersScope::class
])]

class Practicante extends Model
{
    protected $fillable = [
        'user_id',
        'discord_id',
        'area_id',
        'estado',
        'fecha_inicio',
        'fecha_fin'
    ];

    public function scopeGetOrPaginate($query,int $default =5)
    {
        $perPage = request('perPage',$default);
        return $query->paginate($perPage);
    }

    public function area()
    {
        return $this->belongsTo(Area::class,'area_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public array $allowedFilters = ['user.name','status'];

}
