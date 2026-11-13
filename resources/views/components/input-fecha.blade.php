@props([
    'name',
    'id' => null,
    'value' => null,
    'placeholder' => 'Selecciona una fecha',
    'min' => null, // formato YYYY-MM-DD
    'max' => null, // formato YYYY-MM-DD
])

@php
    $id = $id ?? $name;
    $value = $value ? substr((string) $value, 0, 10) : '';
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        isOpen: false,
        value: @js($value),
        min: @js($min),
        max: @js($max),
        month: 0,
        year: 0,
        months: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
        days: ['Lu','Ma','Mi','Ju','Vi','Sá','Do'],

        init() {
            const base = this.parse(this.value) ?? new Date();
            this.month = base.getMonth();
            this.year = base.getFullYear();
        },
        parse(s) {
            if (!s) return null;
            const [y, m, d] = s.split('-').map(Number);
            return new Date(y, m - 1, d);
        },
        iso(day) {
            return this.year + '-' + String(this.month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        },
        get label() {
            const d = this.parse(this.value);
            return d ? d.getDate() + ' de ' + this.months[d.getMonth()].toLowerCase() + ' de ' + d.getFullYear() : '';
        },
        get blanks() {
            // La semana empieza en lunes
            return (new Date(this.year, this.month, 1).getDay() + 6) % 7;
        },
        get daysInMonth() {
            return new Date(this.year, this.month + 1, 0).getDate();
        },
        prev() {
            if (this.month === 0) { this.month = 11; this.year--; } else { this.month--; }
        },
        next() {
            if (this.month === 11) { this.month = 0; this.year++; } else { this.month++; }
        },
        isSelected(day) { return this.iso(day) === this.value; },
        isToday(day) {
            const t = new Date();
            return this.iso(day) === t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0');
        },
        isDisabled(day) {
            const i = this.iso(day);
            return (this.min && i < this.min) || (this.max && i > this.max);
        },
        toggle() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                const base = this.parse(this.value) ?? new Date();
                this.month = base.getMonth();
                this.year = base.getFullYear();
            }
        },
        choose(day) {
            if (this.isDisabled(day)) return;
            this.value = this.iso(day);
            this.isOpen = false;
            this.$refs.trigger.focus();
        },
        goToday() {
            const t = new Date();
            this.month = t.getMonth();
            this.year = t.getFullYear();
            this.choose(t.getDate());
        },
        clear() {
            this.value = '';
            this.isOpen = false;
        }
    }"
    x-modelable="value"
    @click.outside="isOpen = false"
    @keydown.escape.window="if (isOpen) { isOpen = false; $refs.trigger.focus(); }"
>
    {{-- Se envía con el formulario solo si hay una fecha seleccionada --}}
    <input type="hidden" name="{{ $name }}" :value="value" :disabled="value === ''">

    <button
        type="button"
        id="{{ $id }}"
        x-ref="trigger"
        @click="toggle()"
        aria-haspopup="dialog"
        :aria-expanded="isOpen"
        class="flex w-full items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-left text-sm focus:border-[#020830] focus:outline-none focus:ring-2 focus:ring-[#020830]/20"
    >
        <span class="truncate" :class="label ? 'text-slate-900' : 'text-slate-400'"
              x-text="label || @js($placeholder)"></span>
        <i class="fa-regular fa-calendar shrink-0 text-xs text-slate-400" aria-hidden="true"></i>
    </button>

    <div
        x-show="isOpen"
        x-cloak
        x-transition.opacity.duration.100ms
        class="absolute z-30 mt-1 w-72 rounded-lg border border-slate-200 bg-white p-3 shadow-lg"
    >
        {{-- Cabecera: mes y año --}}
        <div class="mb-2 flex items-center justify-between">
            <div class="text-sm font-semibold text-slate-800">
                <span x-text="months[month]"></span>
                <span class="ml-1 font-normal text-slate-500" x-text="year"></span>
            </div>
            <div class="flex gap-1">
                <button type="button" @click="prev()" aria-label="Mes anterior"
                        class="flex h-7 w-7 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100">
                    <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
                </button>
                <button type="button" @click="next()" aria-label="Mes siguiente"
                        class="flex h-7 w-7 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100">
                    <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        {{-- Días de la semana --}}
        <div class="mb-1 grid grid-cols-7 text-center text-xs font-medium text-slate-400">
            <template x-for="d in days" :key="d">
                <div class="py-1" x-text="d"></div>
            </template>
        </div>

        {{-- Días del mes --}}
        <div class="grid grid-cols-7 gap-y-1 text-center text-sm">
            <template x-for="b in blanks" :key="'b' + b">
                <div></div>
            </template>
            <template x-for="day in daysInMonth" :key="day">
                <button
                    type="button"
                    @click="choose(day)"
                    :disabled="isDisabled(day)"
                    class="mx-auto flex h-8 w-8 items-center justify-center rounded-md transition disabled:cursor-not-allowed disabled:opacity-30"
                    :class="isSelected(day)
                        ? 'bg-[#020830] font-medium text-white'
                        : (isToday(day)
                            ? 'border border-[#020830]/40 text-[#020830] hover:bg-slate-100'
                            : 'text-slate-700 hover:bg-slate-100')"
                    x-text="day"
                ></button>
            </template>
        </div>

        {{-- Pie --}}
        <div class="mt-3 flex justify-between border-t border-slate-100 pt-2 text-xs">
            <button type="button" @click="clear()" class="text-slate-500 hover:text-slate-700">Limpiar</button>
            <button type="button" @click="goToday()" class="font-medium text-[#020830] hover:underline">Hoy</button>
        </div>
    </div>
</div>
