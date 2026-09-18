<?php

namespace App\Models;

use App\Models\Scopes\FiltersScope;
use Illuminate\Database\Eloquent\Model;

class Scope extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScopes([
            FiltersScope::class
        ]);
    }

    public function scopeGetOrPaginate($query)
    {
        if (request('per_page')) {
            $perPage = request('per_page');
            return $query->paginate($perPage);
        }

        return $query->get();

    }

}
