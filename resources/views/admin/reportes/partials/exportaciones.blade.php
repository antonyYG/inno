{{-- EXPORTACIONES --}}
<div class="flex flex-wrap gap-3">

    <button
        type="button"
        @click="exportar('excel')"
        :disabled="!buscado || cargando"
        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-green-700 disabled:opacity-50"
    >
        <i class="fa-solid fa-file-excel"></i>
        Exportar Excel
    </button>

    <button
        type="button"
        @click="exportar('pdf')"
        :disabled="!buscado || cargando"
        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 disabled:opacity-50"
    >
        <i class="fa-solid fa-file-pdf"></i>
        Exportar PDF
    </button>

</div>
