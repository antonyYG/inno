<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FiltersScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (empty(request('filters'))) {
            return;
        }

        $filters = request('filters');

        foreach ($filters as $field => $conditions) {
            foreach ($conditions as $operator => $value) {
                if (in_array($operator,['=','>','<','>=','<=','!='])) {
                    $builder->where($field, $operator, $value);
                }

                if ($operator === 'like') {
                    $builder->where($field, 'like', "%$value%");
                }
            }
        }


    }
}
