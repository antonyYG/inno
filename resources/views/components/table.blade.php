@props([
    'columns' => [],
    'rows',
    'searchable' => true,
    'searchFields' => [],
    'selectFilters' => [],
    'perPageOptions' => [5, 10, 25, 30],
    'emptyMessage' => 'No se encontraron resultados.',
    'selectable' => false,
    'actionsColumn' => false,
])

@php
    /*
    |--------------------------------------------------------------------------
    | Normalización defensiva
    |--------------------------------------------------------------------------
    |
    | Permite pasar 'columns' y 'selectFilters' como arrays de strings
    | sueltos (atajo) o como arrays asociativos completos. Evita el
    | error "Cannot access offset of type string on string" cuando a
    | algún elemento le falta la forma ['key' => ..., ...].
    |
    */

    $columns = collect($columns)->map(function ($column, $index) {
        if (is_string($column)) {
            return [
                'key' => $column,
                'label' => \Illuminate\Support\Str::headline($column),
            ];
        }

        return $column;
    })->all();

    $selectFilters = collect($selectFilters)->map(function ($filter) {
        if (is_string($filter)) {
            return [
                'key' => $filter,
                'label' => \Illuminate\Support\Str::headline($filter),
                'options' => [],
                'operator' => '=',
            ];
        }

        $filter['options'] = $filter['options'] ?? [];
        // Operador que espera FiltersScope dentro de filters[key][operador].
        // Por defecto '=' (match exacto); pásalo tú si tu scope usa otro.
        $filter['operator'] = $filter['operator'] ?? '=';

        return $filter;
    })->all();

    $searchFields = collect($searchFields)->map(function ($field) {
        if (is_string($field)) {
            return [
                'key' => $field,
                'label' => \Illuminate\Support\Str::headline($field),
            ];
        }

        return $field;
    })->all();

    $sort = request('sort');
    $direction = request('direction', 'asc');

    $sortUrl = function (string $key) use ($sort, $direction) {
        $newDirection = ($sort === $key && $direction === 'asc')
            ? 'desc'
            : 'asc';

        return request()->fullUrlWithQuery([
            'sort' => $key,
            'direction' => $newDirection,
            'page' => 1,
        ]);
    };

    /*
    |--------------------------------------------------------------------------
    | Campo de búsqueda activo
    |--------------------------------------------------------------------------
    */

    $activeSearchField = collect($searchFields)
        ->first(function ($field) {
            return request()->filled("filters.{$field['key']}.like");
        })['key']
        ?? ($searchFields[0]['key'] ?? null);

    $searchValue = $activeSearchField
        ? request("filters.{$activeSearchField}.like")
        : null;

    /*
    |--------------------------------------------------------------------------
    | Valor actual de cada filtro select (status, tipo, etc.)
    |--------------------------------------------------------------------------
    |
    | $selectFilters recibe algo como:
    |
    | [
    |     [
    |         'key' => 'status',
    |         'label' => 'Estado',
    |         'placeholder' => 'Todos',
    |         'options' => ['active' => 'Activo', 'inactive' => 'Inactivo'],
    |     ],
    | ]
    |
    */

    $selectFilters = collect($selectFilters)->map(function ($filter) {
        $filter['value'] = request("filters.{$filter['key']}.{$filter['operator']}", '');
        return $filter;
    });
    // Nota: $selectFilters ya viene normalizado (ver bloque de normalización arriba).

    /*
    |--------------------------------------------------------------------------
    | Query string que debe conservarse
    |--------------------------------------------------------------------------
    |
    | Conservamos: sort, direction, perPage.
    | NO conservamos: filters, page, __single_field
    | (esos los controla el propio formulario de filtros, que ya
    | reenvía búsqueda + selects juntos en cada submit).
    |
    */

    $searchQuery = request()->except([
        'filters',
        'page',
        '__single_field',
    ]);

    $perPageQuery = request()->except([
        'perPage',
        'page',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Limpiar filtros
    |--------------------------------------------------------------------------
    |
    | Solo se muestra el botón si hay búsqueda o algún select activo.
    | Al limpiar, se conservan sort/direction/perPage pero se quita
    | todo lo que sea 'filters'.
    |
    */

    $hasActiveFilters = filled($searchValue)
        || $selectFilters->contains(fn ($filter) => filled($filter['value']));

    $clearFiltersUrl = request()->url() . (
        ! empty($searchQuery) ? '?' . http_build_query($searchQuery) : ''
    );

    $rowCount = $rows instanceof \Countable
        ? count($rows)
        : $rows->count();

    $colSpan = count($columns)
        + ($actionsColumn ? 1 : 0)
        + ($selectable ? 1 : 0);
@endphp


<div x-data="{ selected: [] }" class="w-full">

    {{-- ============================================================
        Barra superior
    ============================================================= --}}

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">

        {{-- ========================================================
            Búsqueda + Filtros (select)
            Van en el MISMO <form> para que al cambiar un select no
            se pierda lo escrito en el buscador, y viceversa.
        ========================================================= --}}

        @if (($searchable && count($searchFields) > 0) || $selectFilters->isNotEmpty())

            <form
                method="GET"
                x-ref="filterForm"
                x-data="{ field: @js($activeSearchField) }"
                class="flex-1 flex flex-wrap items-center gap-2"
            >

                {{-- Conservar otros parámetros (sort, direction, perPage) --}}
                <x-hidden-inputs :data="$searchQuery" />

                {{-- ----------------------------------------------
                    Búsqueda
                ----------------------------------------------- --}}
                @if ($searchable && count($searchFields) > 0)

                    <div class="flex gap-2 max-w-md flex-1">

                        {{-- Selector de campo --}}
                        @if (count($searchFields) > 1)

                            <select
                                x-model="field"
                                x-on:change="$refs.searchInput.value && $refs.filterForm.submit()"
                                class="rounded-lg border-gray-300 text-sm"
                            >
                                @foreach ($searchFields as $searchField)

                                    <option value="{{ $searchField['key'] }}">
                                        {{ $searchField['label'] }}
                                    </option>

                                @endforeach
                            </select>

                        @else

                            <input
                                type="hidden"
                                name="__single_field"
                                value="{{ $searchFields[0]['key'] }}"
                            >

                        @endif


                        {{-- Input de búsqueda --}}
                        <div class="relative flex-1">

                            <input
                                type="text"
                                x-ref="searchInput"
                                x-bind:name="`filters[${field}][like]`"
                                value="{{ $searchValue }}"
                                placeholder="Buscar..."
                                x-on:input.debounce.500ms="$refs.filterForm.submit()"
                                class="w-full rounded-lg border-gray-300 pl-9 pr-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            {{-- Icono --}}
                            <svg
                                class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"
                                />
                            </svg>

                        </div>

                    </div>

                @endif


                {{-- ----------------------------------------------
                    Filtros select (status, tipo, etc.)
                    100% genéricos: se definen desde el componente
                    que consume la tabla, así que sirven para
                    cualquier modelo.
                ----------------------------------------------- --}}
                @foreach ($selectFilters as $filter)

                    <select
                        name="filters[{{ $filter['key'] }}][{{ $filter['operator'] }}]"
                        onchange="this.form.submit()"
                        class="rounded-lg border-gray-300 text-sm"
                    >

                        <option value="">
                            {{ $filter['placeholder'] ?? ('Todos - ' . ($filter['label'] ?? '')) }}
                        </option>

                        @foreach ($filter['options'] as $optionValue => $optionLabel)

                            <option
                                value="{{ $optionValue }}"
                                @selected((string) $filter['value'] === (string) $optionValue)
                            >
                                {{ $optionLabel }}
                            </option>

                        @endforeach

                    </select>

                @endforeach


                {{-- ----------------------------------------------
                    Limpiar filtros
                ----------------------------------------------- --}}
                @if ($hasActiveFilters)

                    <a
                        href="{{ $clearFiltersUrl }}"
                        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 underline underline-offset-2"
                    >
                        Limpiar filtros
                    </a>

                @endif

            </form>

        @endif


        {{-- ========================================================
            Acciones + cantidad por página
        ========================================================= --}}

        <div class="flex items-center gap-3">

            {{-- Botones --}}
            {{ $actions ?? '' }}


            {{-- Registros por página --}}
            @if (method_exists($rows, 'links'))

                <form method="GET">

                    <x-hidden-inputs :data="$perPageQuery" />

                    <select
                        name="perPage"
                        onchange="this.form.submit()"
                        class="rounded-lg border-gray-300 text-sm"
                    >

                        @foreach ($perPageOptions as $option)

                            <option
                                value="{{ $option }}"
                                @selected(request('perPage', 5) == $option)
                            >
                                {{ $option }} / página
                            </option>

                        @endforeach

                    </select>

                </form>

            @endif

        </div>

    </div>


    {{-- ============================================================
        Tabla
    ============================================================= --}}

    <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm">

        <table class="min-w-full divide-y divide-gray-200 text-sm">

            <thead class="bg-gray-50">

                <tr>

                    {{-- Selección --}}
                    @if ($selectable)

                        <th class="w-10 px-4 py-3">

                            <input
                                type="checkbox"
                                x-on:change="
                                    selected = $event.target.checked
                                        ? Array.from(
                                            document.querySelectorAll('[data-row-checkbox]')
                                        ).map(el => el.value)
                                        : []
                                "
                            >

                        </th>

                    @endif


                    {{-- Columnas --}}
                    @foreach ($columns as $column)

                        <th class="px-4 py-3 text-left font-medium text-gray-600 whitespace-nowrap">

                            @if ($column['sortable'] ?? false)

                                <a
                                    href="{{ $sortUrl($column['key']) }}"
                                    class="inline-flex items-center gap-1 hover:text-gray-900"
                                >

                                    {{ $column['label'] }}

                                    @if ($sort === $column['key'])

                                        <span>
                                            {{ $direction === 'asc' ? '↑' : '↓' }}
                                        </span>

                                    @endif

                                </a>

                            @else

                                {{ $column['label'] }}

                            @endif

                        </th>

                    @endforeach


                    {{-- Acciones --}}
                    @if ($actionsColumn)

                        <th class="px-4 py-3 text-right font-medium text-gray-600">
                            Acciones
                        </th>

                    @endif

                </tr>

            </thead>


            {{-- ====================================================
                Filas
            ===================================================== --}}

            <tbody class="divide-y divide-gray-100 bg-white">

                {{ $slot }}

                @if ($rowCount === 0)

                    <tr>

                        <td
                            colspan="{{ max($colSpan, 1) }}"
                            class="px-4 py-8 text-center text-gray-400"
                        >
                            {{ $emptyMessage }}
                        </td>

                    </tr>

                @endif

            </tbody>

        </table>

    </div>


    {{-- ============================================================
        Paginación
    ============================================================= --}}

    @if (method_exists($rows, 'links'))

        <div class="mt-4">

            {{ $rows->appends(request()->query())->links() }}

        </div>

    @endif

</div>
