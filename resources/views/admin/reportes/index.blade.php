
<x-admin-layout>

    <div x-data="reportes()" class="space-y-6">

        <x-header>
            Reportes
        </x-header>

        {{-- FILTROS --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">

            <h2 class="font-semibold text-slate-800">
                Filtros de búsqueda
            </h2>

            {{-- Tipo de reporte --}}
            <div class="max-w-sm">
                <x-select
                    name="tipoReporte"
                    label="Tipo de reporte"
                    x-model="filtros.tipoReporte"
                    @change="cambiarTipoReporte()"
                >
                    <option value="asistencias">Asistencias</option>
                    <option value="evidencias">Evidencias</option>
                </x-select>
            </div>

            <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.5fr)_repeat(3,minmax(0,1fr))_auto]">

                {{-- Practicante --}}
                <x-select-search
                    name="practicante"
                    :options="$practicantes"
                    x-model="filtros.practicante"
                />

                {{-- Fecha desde --}}
                <x-input-fecha
                    name="fechaDesde"
                    x-model="filtros.fechaDesde"
                />

                {{-- Fecha hasta --}}
                <x-input-fecha
                    name="fechaHasta"
                    x-model="filtros.fechaHasta"
                />

                {{-- Estado --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Estado
                    </label>

                    <select
                        x-model="filtros.estadoAsistencia"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Todos los estados</option>

                        <template x-if="filtros.tipoReporte === 'asistencias'">
                            <optgroup label="Estados de asistencia">
                                <option value="asistio">Asistió</option>
                                <option value="tardanza">Tardanza</option>
                                <option value="falto">Falta</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="justificado">Justificado</option>
                            </optgroup>
                        </template>

                        <template x-if="filtros.tipoReporte === 'evidencias'">
                            <optgroup label="Estados de evidencia">
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobada">Aprobada</option>
                                <option value="rechazada">Rechazada</option>
                            </optgroup>
                        </template>
                    </select>
                </div>

                {{-- Buscar --}}
                <div class="sm:col-span-2 lg:col-span-1">
                    <button
                        type="button"
                        @click="buscar()"
                        :disabled="cargando"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50 lg:w-auto"
                    >
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>

                        <span x-show="!cargando">Buscar</span>
                        <span x-show="cargando" x-cloak>Buscando...</span>
                    </button>
                </div>

            </div>
        </div>

        {{-- RESUMEN ESTADÍSTICO --}}
        <div
            x-show="buscado"
            x-cloak
            class="grid grid-cols-2 gap-4 lg:grid-cols-4"
        >

            {{-- Total --}}
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

            {{-- Estadísticas de asistencias --}}
            <template x-if="filtros.tipoReporte === 'asistencias'">
                <div class="contents">

                    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                            <i class="fa-solid fa-user-check"></i>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                Asistencias
                            </p>

                            <p class="text-2xl font-semibold text-slate-800" x-text="contar('asistio')"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <i class="fa-solid fa-user-clock"></i>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                Tardanzas
                            </p>

                            <p class="text-2xl font-semibold text-slate-800" x-text="contar('tardanza')"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                            <i class="fa-solid fa-user-xmark"></i>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                Faltas
                            </p>

                            <p class="text-2xl font-semibold text-slate-800" x-text="contar('falto')"></p>
                        </div>
                    </div>

                </div>
            </template>

            {{-- Estadísticas de evidencias --}}
            <template x-if="filtros.tipoReporte === 'evidencias'">
                <div class="contents">

                    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                            <i class="fa-solid fa-clock"></i>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                Pendientes
                            </p>

                            <p class="text-2xl font-semibold text-slate-800" x-text="contar('pendiente')"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                Aprobadas
                            </p>

                            <p class="text-2xl font-semibold text-slate-800" x-text="contar('aprobada')"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                Rechazadas
                            </p>

                            <p class="text-2xl font-semibold text-slate-800" x-text="contar('rechazada')"></p>
                        </div>
                    </div>

                </div>
            </template>

        </div>

        {{-- RESULTADOS --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h2 class="font-semibold text-slate-800">
                        Resultados
                    </h2>

                    <p class="text-sm text-slate-500">
                        <span x-text="total"></span> registros encontrados
                    </p>
                </div>

                {{-- EXPORTACIONES --}}
                <div class="flex flex-wrap gap-3">

                    <button
                        type="button"
                        @click="exportar('excel')"
                        :disabled="!buscado || cargando"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        <i class="fa-solid fa-file-excel"></i>
                        Exportar Excel
                    </button>

                    <button
                        type="button"
                        @click="exportar('pdf')"
                        :disabled="!buscado || cargando"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        <i class="fa-solid fa-file-pdf"></i>
                        Exportar PDF
                    </button>

                </div>

            </div>

            {{-- Sin búsqueda --}}
            <template x-if="!buscado">
                <p class="py-8 text-center text-sm text-slate-500">
                    Selecciona los filtros y presiona Buscar reporte.
                </p>
            </template>

            {{-- Sin resultados --}}
            <template x-if="buscado && resultados.length === 0">
                <p class="py-8 text-center text-sm text-slate-500">
                    No se encontraron registros.
                </p>
            </template>

            {{-- TABLA --}}
            <template x-if="buscado && resultados.length > 0">
                <div class="overflow-x-auto rounded-lg border border-slate-200">

                    <table class="w-full min-w-[600px] text-left text-sm">

                        <thead class="border-b border-slate-200 bg-slate-100 text-xs uppercase tracking-wider text-slate-600">
                            <tr>

                                <th class="px-5 py-3 font-semibold">
                                    Practicante
                                </th>

                                <th class="px-5 py-3 font-semibold">
                                    Fecha
                                </th>

                                <th class="px-5 py-3 font-semibold"
                                    x-text="filtros.tipoReporte === 'evidencias' ? 'Descripción' : 'Estado'">
                                </th>

                                <template x-if="filtros.tipoReporte === 'evidencias'">
                                    <th class="px-5 py-3 font-semibold">
                                        Estado
                                    </th>
                                </template>

                                <template x-if="filtros.tipoReporte === 'evidencias'">
                                    <th class="px-5 py-3 font-semibold text-center">
                                        Archivo
                                    </th>
                                </template>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <template x-for="item in resultados" :key="item.id">

                                <tr class="transition-colors hover:bg-slate-50">

                                    <td class="px-5 py-3 font-medium text-slate-800"
                                        x-text="item.practicante">
                                    </td>

                                    <td class="px-5 py-3 text-slate-600"
                                        x-text="item.fecha">
                                    </td>

                                    {{-- Asistencia o descripción de evidencia --}}
                                    <td class="px-5 py-3 text-slate-600">

                                        <template x-if="filtros.tipoReporte === 'asistencias'">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                                :class="estadoClase(item.estado)"
                                                x-text="estadoTexto(item.estado)">
                                            </span>
                                        </template>

                                        <template x-if="filtros.tipoReporte === 'evidencias'">
                                            <span
                                                class="block max-w-xs truncate"
                                                :title="item.descripcion"
                                                x-text="item.descripcion || 'Sin descripción'">
                                            </span>
                                        </template>

                                    </td>

                                    {{-- Estado de evidencia --}}
                                    <template x-if="filtros.tipoReporte === 'evidencias'">
                                        <td class="px-5 py-3">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                                :class="estadoClase(item.estado)"
                                                x-text="estadoTexto(item.estado)">
                                            </span>
                                        </td>
                                    </template>

                                    {{-- Archivo --}}
                                    <template x-if="filtros.tipoReporte === 'evidencias'">
                                        <td class="px-5 py-3 text-center">

                                            <button
                                                type="button"
                                                @click="abrirEvidencia(item)"
                                                :disabled="!item.archivo"
                                                class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-medium text-indigo-600 hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                <i class="fa-solid fa-eye"></i>
                                                Visualizar
                                            </button>

                                        </td>
                                    </template>

                                </tr>

                            </template>

                        </tbody>

                    </table>

                </div>
            </template>

        </div>

        {{-- DRAWER DE EVIDENCIA --}}
        <div
            x-show="drawerAbierto"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[100] flex justify-end"
        >

            {{-- Fondo --}}
            <div
                class="absolute inset-0 bg-black/40"
                @click="cerrarEvidencia()"
            ></div>

            {{-- Panel --}}
            <div
                x-show="drawerAbierto"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="relative z-10 flex h-full w-full max-w-xl flex-col bg-white shadow-xl"
            >

                {{-- Encabezado --}}
                <div class="flex items-center justify-between border-b border-slate-200 p-5">

                    <div>
                        <h2 class="font-semibold text-slate-800">
                            Detalle de evidencia
                        </h2>

                        <p class="text-sm text-slate-500">
                            Información del archivo presentado
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="cerrarEvidencia()"
                        class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100"
                    >
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>

                </div>

                {{-- Contenido --}}
                <div
                    x-show="evidenciaSeleccionada"
                    class="flex-1 space-y-5 overflow-y-auto p-5"
                >

                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            Practicante
                        </p>

                        <p
                            class="mt-1 font-medium text-slate-800"
                            x-text="evidenciaSeleccionada?.practicante"
                        ></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            Fecha
                        </p>

                        <p
                            class="mt-1 text-sm text-slate-700"
                            x-text="evidenciaSeleccionada?.fecha"
                        ></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            Descripción
                        </p>

                        <p
                            class="mt-1 text-sm text-slate-700"
                            x-text="evidenciaSeleccionada?.descripcion || 'Sin descripción'"
                        ></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            Estado
                        </p>

                        <span
                            class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="estadoClase(evidenciaSeleccionada?.estado)"
                            x-text="estadoTexto(evidenciaSeleccionada?.estado)"
                        ></span>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500">
                            Observación
                        </p>

                        <p
                            class="mt-1 text-sm text-slate-700"
                            x-text="evidenciaSeleccionada?.observacion || 'Sin observación'"
                        ></p>
                    </div>

                    {{-- Archivo adjunto --}}
                    <template x-if="evidenciaSeleccionada?.archivo">

                        <div class="rounded-xl border border-slate-200 p-4">

                            <p class="mb-3 text-sm font-semibold text-slate-700">
                                Archivo adjunto
                            </p>

                            <p
                                class="mb-3 break-all text-sm text-slate-500"
                                x-text="evidenciaSeleccionada?.nombre_archivo || 'Archivo de evidencia'"
                            ></p>

                            <a
                                :href="evidenciaSeleccionada.archivo"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700"
                            >
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                Abrir archivo
                            </a>

                        </div>

                    </template>

                    <template x-if="!evidenciaSeleccionada?.archivo">
                        <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-700">
                            Esta evidencia no tiene un archivo adjunto disponible.
                        </div>
                    </template>

                </div>

            </div>

        </div>

    </div>

    <script>
        function reportes() {
            return {
                filtros: {
                    tipoReporte: 'asistencias',
                    practicante: '',
                    fechaDesde: '',
                    fechaHasta: '',
                    estadoAsistencia: '',
                },

                resultados: [],
                total: 0,
                resumen: {},

                cargando: false,
                buscado: false,

                drawerAbierto: false,
                evidenciaSeleccionada: null,

                // CONSULTAR REPORTE
                async buscar() {

                    this.cargando = true;

                    try {

                        const evidencias = this.filtros.tipoReporte === 'evidencias';

                        const ruta = evidencias
                            ? @js(route('admin.reportes.filtrar.evidencias'))
                            : @js(route('admin.reportes.filtrar'));

                        const params = new URLSearchParams({
                            practicante: this.filtros.practicante || '',
                            fechaDesde: this.filtros.fechaDesde || '',
                            fechaHasta: this.filtros.fechaHasta || '',
                            estadoAsistencia: this.filtros.estadoAsistencia || '',
                        });

                        const response = await fetch(`${ruta}?${params.toString()}`, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        });

                        if (!response.ok) {
                            throw new Error('No se pudo realizar la consulta.');
                        }

                        const data = await response.json();

                        this.resultados = data.data ?? [];
                        this.total = data.total ?? 0;
                        this.resumen = data.resumen ?? {};

                        this.buscado = true;

                    } catch (error) {

                        console.error(error);

                        Swal.fire(
                            'Error',
                            error.message || 'Ocurrió un error al consultar el reporte.',
                            'error'
                        );

                    } finally {
                        this.cargando = false;
                    }
                },

                // CONTAR REGISTROS POR ESTADO
                contar(estado) {
                    return this.resultados.filter(
                        item => item.estado === estado
                    ).length;
                },

                // TEXTO DE ESTADOS
                estadoTexto(estado) {

                    const estados = {
                        asistio: 'Asistió',
                        tardanza: 'Tardanza',
                        falto: 'Falta',
                        pendiente: 'Pendiente',
                        justificado: 'Justificado',
                        aprobada: 'Aprobada',
                        rechazada: 'Rechazada',
                    };

                    return estados[estado] ?? estado ?? 'Sin estado';
                },

                // COLORES DE ESTADOS
                estadoClase(estado) {

                    const clases = {
                        asistio: 'bg-green-50 text-green-700 ring-green-600/20',
                        aprobada: 'bg-green-50 text-green-700 ring-green-600/20',

                        tardanza: 'bg-amber-50 text-amber-700 ring-amber-600/20',
                        pendiente: 'bg-amber-50 text-amber-700 ring-amber-600/20',

                        falto: 'bg-red-50 text-red-700 ring-red-600/20',
                        rechazada: 'bg-red-50 text-red-700 ring-red-600/20',

                        justificado: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    };

                    return clases[estado] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';
                },

                // CAMBIAR TIPO DE REPORTE
                cambiarTipoReporte() {

                    this.resultados = [];
                    this.total = 0;
                    this.resumen = {};

                    this.buscado = false;

                    this.filtros.estadoAsistencia = '';

                    this.cerrarEvidencia();
                },

                // ABRIR DRAWER
                abrirEvidencia(item) {

                    this.evidenciaSeleccionada = item;
                    this.drawerAbierto = true;
                },

                // CERRAR DRAWER
                cerrarEvidencia() {

                    this.drawerAbierto = false;
                    this.evidenciaSeleccionada = null;
                },

                // EXPORTAR PDF O EXCEL
                exportar(formato) {

                    if (!this.buscado) {
                        Swal.fire(
                            'Atención',
                            'Primero debes realizar una búsqueda.',
                            'warning'
                        );

                        return;
                    }

                    const evidencias = this.filtros.tipoReporte === 'evidencias';

                    const rutas = evidencias
                        ? {
                            pdf: @js(route('admin.reportes.exportar.evidencias.pdf')),
                            excel: @js(route('admin.reportes.exportar.evidencias.excel')),
                        }
                        : {
                            pdf: @js(route('admin.reportes.exportar.pdf')),
                            excel: @js(route('admin.reportes.exportar.excel')),
                        };

                    const params = new URLSearchParams({
                        practicante: this.filtros.practicante || '',
                        fechaDesde: this.filtros.fechaDesde || '',
                        fechaHasta: this.filtros.fechaHasta || '',
                        estadoAsistencia: this.filtros.estadoAsistencia || '',
                    });

                    window.location.href = `${rutas[formato]}?${params.toString()}`;
                },
            };
        }
    </script>

</x-admin-layout>
