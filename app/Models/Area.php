<?php

namespace App\Models;

use App\Models\Scopes\FiltersScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy(FiltersScope::class)]

class Area extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function scopeGetOrPaginate($query,int $default=5)
    {
        $perPage = request('perPage',$default);
        return $query->paginate($perPage);
    }

}
