{{-- PAGINACIÓN COMPARTIDA --}}
<div
    x-show="paginacion.last_page > 1"
    x-cloak
    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
>
    <p class="text-sm text-slate-500">
        Página <span x-text="paginacion.current_page"></span> de
        <span x-text="paginacion.last_page"></span>
    </p>

    <div class="flex flex-wrap items-center gap-2">
        <button
            type="button"
            @click="cambiarPagina(paginacion.current_page - 1)"
            :disabled="!paginacion.prev_page_url"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
            Anterior
        </button>

        <template x-for="enlace in paginacion.links" :key="enlace.page">
            <button
                type="button"
                @click="cambiarPagina(enlace.page)"
                :class="enlace.active
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'border-slate-300 text-slate-700 hover:bg-slate-50'"
                class="rounded-lg border px-3 py-1.5 text-sm font-medium transition"
                x-text="enlace.page"
            ></button>
        </template>

        <button
            type="button"
            @click="cambiarPagina(paginacion.current_page + 1)"
            :disabled="!paginacion.next_page_url"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
            Siguiente
        </button>
    </div>
</div>
