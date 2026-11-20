{{-- RESUMEN DE RESULTADOS --}}
<div
    x-show="buscado"
    x-cloak
    class="grid grid-cols-2 gap-4 lg:grid-cols-4"
>
    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
            <i class="fa-solid fa-list-check"></i>
        </div>

        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Total de registros
            </p>

            <p class="text-2xl font-semibold text-slate-800" x-text="total"></p>
        </div>
    </div>

    <template x-if="filtros.tipoReporte === 'asistencias'">
        <div class="contents">
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Asistió</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('asistio')"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tardanzas</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('tardanza')"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Faltas</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('falto')"></p>
                </div>
            </div>
        </div>
    </template>

    <template x-if="filtros.tipoReporte === 'evidencias'">
        <div class="contents">
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pendientes</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('pendiente')"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Aprobadas</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('aprobada')"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Rechazadas</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('rechazada')"></p>
                </div>
            </div>
        </div>
    </template>

    <template x-if="filtros.tipoReporte === 'auditorias'">
        <div class="contents">
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Aceptadas</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('aceptada')"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Rechazadas</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('rechazada')"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>

                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Actividades</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('actividad')"></p>
                </div>
            </div>
        </div>
    </template>
</div>
