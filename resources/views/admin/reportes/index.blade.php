<x-admin-layout>

    <div x-data="reportes()" class="space-y-6">

        <x-header>
            Reportes
        </x-header>

        {{-- Filtros --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">

            <h2 class="font-semibold text-slate-800">
                Filtros de búsqueda
            </h2>

            {{-- Se conserva el tipo de reporte (único valor actual) sin ocupar espacio en la fila --}}
            <input type="hidden" name="tipoReporte" value="general">

            <div
                class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1.5fr)_repeat(3,minmax(0,1fr))_auto]">

                {{-- Practicante --}}
                <x-select-search name="practicante" :options="$practicantes" x-model="filtros.practicante" />

                {{-- Fecha desde --}}
                <x-input-fecha name="fechaDesde" x-model="filtros.fechaDesde" />

                {{-- Fecha hasta --}}
                <x-input-fecha name="fechaHasta" x-model="filtros.fechaHasta" />

                {{-- Estado --}}
                <x-select name="estadoAsistencia" label="Estado de asistencia" x-model="filtros.estadoAsistencia">
                    <option value="">Todos los estados</option>
                    <option value="asistio">Asistió</option>
                    <option value="tardanza">Tardanza</option>
                    <option value="falto">Falta</option>
                </x-select>

                {{-- Botón buscar --}}
                <div class="sm:col-span-2 lg:col-span-1">
                    <button type="button" @click="buscar()" :disabled="cargando"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50 lg:w-auto">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        <span x-show="!cargando">Buscar</span>
                        <span x-show="cargando" x-cloak>Buscando...</span>
                    </button>
                </div>

            </div>

        </div>

        {{-- Resumen estadístico (calculado con los resultados filtrados) --}}
        <div x-show="buscado" x-cloak class="grid grid-cols-2 gap-4 lg:grid-cols-4">

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total de registros</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="total"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Asistencias</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('asistio')"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tardanzas</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('tardanza')"></p>
                </div>
            </div>

            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <i class="fa-solid fa-user-xmark"></i>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Faltas</p>
                    <p class="text-2xl font-semibold text-slate-800" x-text="contar('falto')"></p>
                </div>
            </div>

        </div>

        {{-- Resultados --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-4">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h2 class="font-semibold text-slate-800">
                        Resultados
                    </h2>

                    <p class="text-sm text-slate-500">
                        <span x-text="total"></span> registros
                    </p>
                </div>

                {{-- Exportar reportes --}}
                <div class="flex gap-3">

                    <button type="button" @click="exportar('excel')"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700">
                        <i class="fa-solid fa-file-excel"></i>
                        Exportar Excel
                    </button>

                    <button type="button" @click="exportar('pdf')"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700">
                        <i class="fa-solid fa-file-pdf"></i>
                        Exportar PDF
                    </button>

                </div>

            </div>

            <template x-if="!buscado">
                <p class="py-8 text-center text-sm text-slate-500">
                    Selecciona los filtros y presiona Buscar reporte.
                </p>
            </template>

            <template x-if="buscado && resultados.length === 0">
                <p class="py-8 text-center text-sm text-slate-500">
                    No se encontraron registros.
                </p>
            </template>

            <template x-if="resultados.length > 0">

                <div class="overflow-x-auto rounded-lg border border-slate-200">

                    <table class="w-full min-w-[480px] text-left text-sm">

                        <thead class="border-b border-slate-200 bg-slate-100 text-xs uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Practicante</th>
                                <th class="px-5 py-3 font-semibold">Fecha</th>
                                <th class="px-5 py-3 font-semibold">Estado</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <template x-for="(item, index) in resultados" :key="index">

                                <tr class="transition-colors hover:bg-slate-50">
                                    <td class="px-5 py-3 font-medium text-slate-800" x-text="item.practicante"></td>

                                    <td class="px-5 py-3 text-slate-600" x-text="item.fecha"></td>

                                    <td class="px-5 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                            :class="estadoClase(item.estado)" x-text="estadoTexto(item.estado)"></span>
                                    </td>
                                </tr>

                            </template>

                        </tbody>

                    </table>

                </div>

            </template>

        </div>

    </div>

    <script>
        function reportes() {
            return {
                filtros: {
                    practicante: '',
                    fechaDesde: '',
                    fechaHasta: '',
                    estadoAsistencia: '',
                },

                resultados: [],
                total: 0,
                cargando: false,
                buscado: false,

                async buscar() {
                    this.cargando = true;

                    try {
                        const params = new URLSearchParams(this.filtros);

                        const response = await fetch(
                            `{{ route('admin.reportes.filtrar') }}?${params}`, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                }
                            }
                        );

                        if (!response.ok) {
                            throw new Error('No se pudo realizar la consulta.');
                        }

                        const data = await response.json();

                        this.resultados = data.data;
                        this.total = data.total;
                        this.buscado = true;

                    } catch (error) {
                        Swal.fire('Error', error.message, 'error');
                    } finally {
                        this.cargando = false;
                    }
                },

                // Conteo por estado sobre los resultados ya filtrados
                contar(estado) {
                    return this.resultados.filter(item => item.estado === estado).length;
                },

                estadoTexto(estado) {
                    return estado === 'asistio' ? 'Asistió' : estado === 'tardanza' ? 'Tardanza' : 'Falta';
                },

                estadoClase(estado) {
                    if (estado === 'asistio') return 'bg-green-50 text-green-700 ring-green-600/20';
                    if (estado === 'tardanza') return 'bg-amber-50 text-amber-700 ring-amber-600/20';
                    return 'bg-red-50 text-red-700 ring-red-600/20';
                },

                exportar(formato) {
                    const rutas = {
                        pdf: @js(route('admin.reportes.exportar.pdf')),
                        excel: @js(route('admin.reportes.exportar.excel')),
                    };

                    const params = new URLSearchParams(this.filtros);

                    window.location.href = `${rutas[formato]}?${params}`;
                }
            }
        }
    </script>

</x-admin-layout>
