<x-admin-layout>

    <div x-data="reportes()" class="space-y-6">

        <x-header>
            Reportes
        </x-header>

        {{-- TÍTULO Y DESCRIPCIÓN --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-semibold text-slate-800" x-text="titulo()"></h2>

            <p class="mt-1 text-sm text-slate-500" x-text="descripcion()"></p>
        </div>

        @include('admin.reportes.partials.filtros')

        @include('admin.reportes.partials.resumen')

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

                @include('admin.reportes.partials.exportaciones')
            </div>

            <template x-if="!buscado">
                <p class="py-8 text-center text-sm text-slate-500">
                    Selecciona los filtros y presiona Buscar para consultar el reporte.
                </p>
            </template>

            <template x-if="buscado && resultados.length === 0">
                <p class="py-8 text-center text-sm text-slate-500">
                    No se encontraron registros con los filtros seleccionados.
                </p>
            </template>

            <template x-if="buscado && resultados.length > 0">
                @include('admin.reportes.partials.tabla')
            </template>

        </div>

        {{-- DRAWER DE EVIDENCIA --}}
        <div
            x-show="drawerAbierto"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[100] flex justify-end"
        >
            <div
                class="absolute inset-0 bg-black/40"
                @click="cerrarEvidencia()"
            ></div>

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

                <div
                    x-show="evidenciaSeleccionada"
                    class="flex-1 space-y-5 overflow-y-auto p-5"
                >
                    <div>
                        <p class="text-xs font-medium text-slate-500">Practicante</p>

                        <p
                            class="mt-1 font-medium text-slate-800"
                            x-text="evidenciaSeleccionada?.practicante"
                        ></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500">Área</p>

                        <p
                            class="mt-1 text-sm text-slate-700"
                            x-text="evidenciaSeleccionada?.area"
                        ></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500">Fecha de presentación</p>

                        <p
                            class="mt-1 text-sm text-slate-700"
                            x-text="evidenciaSeleccionada?.fecha"
                        ></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500">Descripción</p>

                        <p
                            class="mt-1 text-sm text-slate-700"
                            x-text="evidenciaSeleccionada?.descripcion"
                        ></p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500">Estado</p>

                        <span
                            class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="estadoClase(evidenciaSeleccionada?.estado)"
                            x-text="estadoTexto(evidenciaSeleccionada?.estado)"
                        ></span>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-500">Observación</p>

                        <p
                            class="mt-1 text-sm text-slate-700"
                            x-text="evidenciaSeleccionada?.observacion"
                        ></p>
                    </div>

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
                    tipoReporte: @js($tipoReporte),
                    practicante: '',
                    administrador: '',
                    fechaDesde: '',
                    fechaHasta: '',
                    estadoAsistencia: '',
                },

                resultados: [],
                total: 0,
                resumen: {},

                pagina: 1,
                paginacion: {
                    current_page: 1,
                    last_page: 1,
                    links: [],
                    prev_page_url: null,
                    next_page_url: null,
                },

                cargando: false,
                buscado: false,

                drawerAbierto: false,
                evidenciaSeleccionada: null,

                titulo() {
                    const titulos = {
                        asistencias: 'Reporte de asistencias',
                        evidencias: 'Reporte de evidencias',
                        auditorias: 'Auditoría general',
                    };

                    return titulos[this.filtros.tipoReporte] ?? 'Reportes';
                },

                descripcion() {
                    const descripciones = {
                        asistencias: 'Asistencias, tardanzas, faltas y horas trabajadas por practicante y rango de fechas.',
                        evidencias: 'Evidencias presentadas, pendientes, aprobadas y rechazadas según los estados reales del sistema.',
                        auditorias: 'Acciones registradas en activity_log: decisiones de justificaciones, evidencias y actividades.',
                    };

                    return descripciones[this.filtros.tipoReporte] ?? '';
                },

                async buscar({ mantenerPagina = false } = {}) {
                    if (!mantenerPagina) {
                        this.pagina = 1;
                    }

                    this.cargando = true;

                    try {
                        const tipo = this.filtros.tipoReporte;

                        const rutas = {
                            asistencias: @js(route('admin.reportes.filtrar')),
                            evidencias: @js(route('admin.reportes.filtrar.evidencias')),
                            auditorias: @js(route('admin.reportes.filtrar.auditorias')),
                        };

                        const params = new URLSearchParams(this.parametros());
                        params.set('per_page', '15');
                        params.set('page', String(this.pagina));

                        const response = await fetch(`${rutas[tipo]}?${params.toString()}`, {
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
                        this.paginacion = data.pagination ?? this.paginacion;
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

                parametros() {
                    return {
                        practicante: this.filtros.practicante || '',
                        administrador: this.filtros.administrador || '',
                        fechaDesde: this.filtros.fechaDesde || '',
                        fechaHasta: this.filtros.fechaHasta || '',
                        estadoAsistencia: this.filtros.estadoAsistencia || '',
                    };
                },

                etiquetaEstado() {
                    if (this.filtros.tipoReporte === 'evidencias') {
                        return 'Estado de evidencia';
                    }

                    if (this.filtros.tipoReporte === 'auditorias') {
                        return 'Acción';
                    }

                    return 'Estado';
                },

                placeholderEstado() {
                    if (this.filtros.tipoReporte === 'auditorias') {
                        return 'Todas las acciones';
                    }

                    return 'Todos los estados';
                },

                cambiarPagina(pagina) {
                    if (pagina < 1 || pagina > this.paginacion.last_page || pagina === this.pagina) {
                        return;
                    }

                    this.pagina = pagina;
                    this.buscar({ mantenerPagina: true });
                },

                contar(estado) {
                    return this.resumen[estado] ?? 0;
                },

                estadoTexto(estado) {
                    const estados = {
                        asistio: 'Asistió',
                        tardanza: 'Tardanza',
                        falto: 'Falta',
                        pendiente: 'Pendiente',
                        justificado: 'Justificado',
                        aprobada: 'Aprobada',
                        aceptada: 'Aceptada',
                        rechazada: 'Rechazada',
                        actividad: 'Registrada',
                    };

                    return estados[estado] ?? estado ?? 'Sin estado';
                },

                estadoClase(estado) {
                    const clases = {
                        asistio: 'bg-green-50 text-green-700 ring-green-600/20',
                        aprobada: 'bg-green-50 text-green-700 ring-green-600/20',
                        aceptada: 'bg-green-50 text-green-700 ring-green-600/20',

                        tardanza: 'bg-amber-50 text-amber-700 ring-amber-600/20',
                        pendiente: 'bg-amber-50 text-amber-700 ring-amber-600/20',

                        falto: 'bg-red-50 text-red-700 ring-red-600/20',
                        rechazada: 'bg-red-50 text-red-700 ring-red-600/20',

                        justificado: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                        actividad: 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    };

                    return clases[estado] ?? 'bg-slate-50 text-slate-700 ring-slate-600/20';
                },

                cambiarTipoReporte() {
                    this.limpiar({ consultar: false });
                    this.cerrarEvidencia();
                },

                limpiar({ consultar = true } = {}) {
                    this.filtros.practicante = '';
                    this.filtros.administrador = '';
                    this.filtros.fechaDesde = '';
                    this.filtros.fechaHasta = '';
                    this.filtros.estadoAsistencia = '';

                    this.resultados = [];
                    this.total = 0;
                    this.resumen = {};
                    this.pagina = 1;
                    this.paginacion = {
                        current_page: 1,
                        last_page: 1,
                        links: [],
                        prev_page_url: null,
                        next_page_url: null,
                    };
                    this.buscado = false;

                    if (consultar) {
                        this.buscar();
                    }
                },

                abrirEvidencia(item) {
                    this.evidenciaSeleccionada = item;
                    this.drawerAbierto = true;
                },

                cerrarEvidencia() {
                    this.drawerAbierto = false;
                    this.evidenciaSeleccionada = null;
                },

                exportar(formato) {
                    if (!this.buscado) {
                        Swal.fire(
                            'Atención',
                            'Primero debes realizar una búsqueda.',
                            'warning'
                        );

                        return;
                    }

                    const tipo = this.filtros.tipoReporte;

                    const rutas = {
                        asistencias: {
                            pdf: @js(route('admin.reportes.exportar.pdf')),
                            excel: @js(route('admin.reportes.exportar.excel')),
                        },
                        evidencias: {
                            pdf: @js(route('admin.reportes.exportar.evidencias.pdf')),
                            excel: @js(route('admin.reportes.exportar.evidencias.excel')),
                        },
                        auditorias: {
                            pdf: @js(route('admin.reportes.exportar.auditorias.pdf')),
                            excel: @js(route('admin.reportes.exportar.auditorias.excel')),
                        },
                    };

                    const params = new URLSearchParams(this.parametros());

                    window.location.href = `${rutas[tipo][formato]}?${params.toString()}`;
                },
            };
        }
    </script>

</x-admin-layout>
