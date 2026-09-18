@props([
    'columns' => [],
    'rows',
    'searchable' => true,
    'searchFields' => [],      // [['key' => 'name', 'label' => 'Nombre'], ['key' => 'email', 'label' => 'Email']]
    'perPageOptions' => [5,10,25,30],
    'emptyMessage' => 'No se encontraron resultados.',
    'selectable' => false,
    'actionsColumn' => false,
])

@php
    $sort = request('sort');
    $direction = request('direction', 'asc');

    $sortUrl = function (string $key) use ($sort, $direction) {
        $newDirection = ($sort === $key && $direction === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery([
            'sort' => $key,
            'direction' => $newDirection,
            'page' => 1,
        ]);
    };

    // Detecta qué campo de búsqueda está activo actualmente en la URL
    $activeSearchField = collect($searchFields)
        ->first(fn ($f) => request()->filled("filters.{$f['key']}.like"))['key']
        ?? ($searchFields[0]['key'] ?? null);

    $searchValue = $activeSearchField ? request("filters.{$activeSearchField}.like") : null;

    // Claves de filtros de búsqueda a excluir al preservar el resto del query string
    $searchFilterKeys = collect($searchFields)->map(fn ($f) => "filters.{$f['key']}")->all();

    $rowCount = $rows instanceof \Countable ? count($rows) : $rows->count();
    $colSpan = count($columns) + ($actionsColumn ? 1 : 0) + ($selectable ? 1 : 0);
@endphp

<div x-data="{ selected: [] }" class="w-full">

    {{-- Barra superior: búsqueda + acciones + selector por página --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">

        @if ($searchable && count($searchFields) > 0)
            <form
                method="GET"
                x-ref="searchForm"
                x-data="{ field: '{{ $activeSearchField }}' }"
                class="flex-1 max-w-md flex gap-2"
            >
                {{-- conserva el resto del query string, excluyendo los filtros de búsqueda y la página --}}
                <x-hidden-inputs :data="request()->except([...$searchFilterKeys, 'page'])" />

                @if (count($searchFields) > 1)
                    <select
                        x-model="field"
                        x-on:change="$refs.searchInput.value && $refs.searchForm.submit()"
                        class="rounded-lg border-gray-300 text-sm"
                    >
                        @foreach ($searchFields as $field)
                            <option value="{{ $field['key'] }}">{{ $field['label'] }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="__single_field" value="{{ $searchFields[0]['key'] }}">
                @endif

                <div class="relative flex-1">
                    <input
                        type="text"
                        x-ref="searchInput"
                        x-bind:name="`filters[${field}][like]`"
                        value="{{ $searchValue }}"
                        placeholder="Buscar..."
                        x-on:input.debounce.500ms="$refs.searchForm.submit()"
                        class="w-full rounded-lg border-gray-300 pl-9 pr-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <svg class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                    </svg>
                </div>
            </form>
        @endif

        <div class="flex items-center gap-3">
            {{-- Slot opcional para botones tipo "Nuevo registro" --}}
            {{ $actions ?? '' }}

            @if (method_exists($rows, 'links'))
                <form method="GET" x-ref="perPageForm">
                    <x-hidden-inputs :data="request()->except(['perPage', 'page'])" />
                    <select name="perPage" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected(request('perPage', 5) == $option)>
                                {{ $option }} / página
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    @if ($selectable)
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox"
                                x-on:change="selected = $event.target.checked
                                    ? Array.from(document.querySelectorAll('[data-row-checkbox]')).map(el => el.value)
                                    : []">
                        </th>
                    @endif

                    @foreach ($columns as $column)
                        <th class="px-4 py-3 text-left font-medium text-gray-600 whitespace-nowrap">
                            @if ($column['sortable'] ?? false)
                                <a href="{{ $sortUrl($column['key']) }}" class="inline-flex items-center gap-1 hover:text-gray-900">
                                    {{ $column['label'] }}
                                    @if ($sort === $column['key'])
                                        <span>{{ $direction === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </a>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach

                    @if ($actionsColumn)
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Acciones</th>
                    @endif
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 bg-white">
                {{-- Cada listado define aquí sus propias filas (<tr>...</tr>) --}}
                {{ $slot }}

                @if ($rowCount === 0)
                    <tr>
                        <td colspan="{{ max($colSpan, 1) }}" class="px-4 py-8 text-center text-gray-400">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    @if (method_exists($rows, 'links'))
        <div class="mt-4">
            {{ $rows->appends(request()->query())->links() }}
        </div>
    @endif
</div>
