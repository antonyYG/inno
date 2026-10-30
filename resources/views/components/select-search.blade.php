{{--
    Uso:
    <x-select-search
        id="area_id"
        name="area_id"
        :options="$areas->pluck('nombre', 'id')"
        :value="old('area_id')"
        placeholder="Selecciona un área"
        search-placeholder="Buscar área..."
    />
    - options: arreglo o colección [valor => etiqueta]
    - value: valor preseleccionado (usa old() para conservarlo tras un error)
--}}
@props([
    'name',
    'id' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Selecciona una opción',
    'searchPlaceholder' => 'Buscar...',
    'emptyText' => 'Sin resultados',
])

@php
    $id = $id ?? $name;
    $items = collect($options)
        ->map(fn ($label, $val) => ['value' => (string) $val, 'label' => (string) $label])
        ->values();
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        isOpen: false,
        search: '',
        value: @js((string) $value),
        items: @js($items),
        get filtered() {
            const term = this.search.trim().toLowerCase();
            return term === '' ? this.items : this.items.filter(i => i.label.toLowerCase().includes(term));
        },
        get selectedLabel() {
            const item = this.items.find(i => i.value === this.value);
            return item ? item.label : '';
        },
        toggle() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) this.$nextTick(() => this.$refs.search.focus());
        },
        close() {
            this.isOpen = false;
            this.search = '';
        },
        choose(item) {
            this.value = item.value;
            this.close();
            this.$refs.trigger.focus();
        },
        chooseFirst() {
            if (this.filtered.length) this.choose(this.filtered[0]);
        }
    }"
    @click.outside="close()"
    @keydown.escape.window="if (isOpen) { close(); $refs.trigger.focus(); }"
>
    {{-- Se envía con el formulario solo si hay un valor seleccionado --}}
    <input type="hidden" name="{{ $name }}" :value="value" :disabled="value === ''">

    <button
        type="button"
        id="{{ $id }}"
        x-ref="trigger"
        @click="toggle()"
        aria-haspopup="listbox"
        :aria-expanded="isOpen"
        class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left text-sm focus:border-[#020830] focus:outline-none focus:ring-2 focus:ring-[#020830]/20"
    >
        <span class="truncate" :class="selectedLabel ? 'text-slate-900' : 'text-slate-400'"
              x-text="selectedLabel || @js($placeholder)"></span>
        <i class="fa-solid fa-chevron-down shrink-0 text-xs text-slate-400 transition-transform"
           :class="isOpen && 'rotate-180'" aria-hidden="true"></i>
    </button>

    <div
        x-show="isOpen"
        x-cloak
        x-transition.opacity.duration.100ms
        class="absolute z-30 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg"
    >
        <div class="border-b border-slate-100 p-2">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                <input
                    type="text"
                    x-ref="search"
                    x-model="search"
                    @keydown.enter.prevent="chooseFirst()"
                    placeholder="{{ $searchPlaceholder }}"
                    autocomplete="off"
                    class="w-full rounded-md border border-slate-200 bg-slate-50 py-2 pl-9 pr-8 text-sm text-slate-800 placeholder-slate-400 focus:border-[#020830] focus:outline-none focus:ring-2 focus:ring-[#020830]/20"
                >
                <button type="button" x-show="search !== ''" x-cloak
                        @click="search = ''; $refs.search.focus()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        aria-label="Limpiar búsqueda">
                    <i class="fa-solid fa-xmark text-sm" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <ul role="listbox" class="max-h-60 overflow-y-auto p-1">
            <template x-for="item in filtered" :key="item.value">
                <li
                    role="option"
                    :aria-selected="item.value === value"
                    @click="choose(item)"
                    class="flex cursor-pointer items-center justify-between gap-2 rounded-md px-3 py-2 text-sm hover:bg-slate-100"
                    :class="item.value === value ? 'bg-slate-50 font-medium text-[#020830]' : 'text-slate-700'"
                >
                    <span class="truncate" x-text="item.label"></span>
                    <i x-show="item.value === value" class="fa-solid fa-check text-xs text-[#020830]" aria-hidden="true"></i>
                </li>
            </template>

            <li x-show="filtered.length === 0" class="px-3 py-3 text-center text-sm text-slate-500">
                {{ $emptyText }}
            </li>
        </ul>
    </div>
</div>
