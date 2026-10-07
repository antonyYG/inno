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

        $allowedFilters  = $model->allowedFilters ?? [];

        $filters = request('filters');

        foreach ($filters as $field => $conditions) {

            if (!in_array($field, $allowedFilters)) {
                continue;
            }


            foreach ($conditions as $operator => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                if (str_contains($field, '.')) {
                    [$relation, $column] = explode('.', $field, 2);
                    $builder->whereHas($relation, function ($query) use (
                        $column,
                        $operator,
                        $value
                    ) {

                        if ($operator === 'like') {
                            $query->where(
                                $column,
                                'like',
                                "%{$value}%"
                            );

                            return;
                        }

                        if (in_array($operator, [
                            '=',
                            '>',
                            '<',
                            '>=',
                            '<=',
                            '!=',
                        ])) {
                            $query->where(
                                $column,
                                $operator,
                                $value
                            );
                        }
                    });

                    continue;
                }
                if (in_array($operator, ['=', '>', '<', '>=', '<=', '!='])) {
                    $builder->where($field, $operator, $value);
                }

                if ($operator === 'like') {
                    $builder->where($field, 'like', "%$value%");
                }
            }
        }
    }
}
