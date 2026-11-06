<x-admin-layout>

    @push('css')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/skeleton.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/monarch/theme.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/monarch/palettes/purple.css" rel="stylesheet" />

        <style>
            .attendance-event {
                display: flex;
                flex-direction: column;
                gap: 3px;
                padding: 3px 5px;
                width: 100%;
                overflow: hidden;
                white-space: normal;
            }

            .attendance-event-name {
                font-size: 12px;
                font-weight: 600;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .attendance-event-detail {
                font-size: 11px;
                opacity: .9;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .attendance-event-status {
                font-size: 10px;
                font-weight: 600;
                text-transform: capitalize;
            }

            .fc .fc-daygrid-event {
                white-space: normal;
            }

            .fc .fc-timegrid-event {
                overflow: hidden;
            }
        </style>
    @endpush

    <div
        x-data="calendarAdmin()"
        x-effect="document.body.classList.toggle('overflow-hidden', drawerOpen)"
        class="relative">

        <!-- Calendario -->
        <div x-ref="calendar"></div>

        <!-- Fondo del drawer -->
        <div
            x-show="drawerOpen"
            x-cloak
            x-transition.opacity
            @click="closeDrawer()"
            class="fixed inset-0 bg-gray-900/40 backdrop-blur-[1px] z-40">
        </div>

        <!-- Drawer administrador -->
        <aside
            x-show="drawerOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            @keydown.escape.window="closeDrawer()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="drawer-title"
            class="fixed top-0 right-0 z-50 h-full w-full sm:max-w-md bg-white shadow-2xl flex flex-col">

            <!-- Encabezado fijo -->
            <header class="shrink-0 flex items-center justify-between gap-4 px-6 py-4 border-b border-gray-100">
                <h2 id="drawer-title" class="text-base font-semibold text-gray-900">
                    Detalle de asistencia
                </h2>

                <button
                    type="button"
                    @click="closeDrawer()"
                    aria-label="Cerrar"
                    class="flex items-center justify-center w-9 h-9 rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </header>

            <!-- Contenido con scroll -->
            <div class="flex-1 overflow-y-auto">
                <template x-if="selectedAttendance">
                    <div class="px-6 py-6 space-y-8">

                        <!-- Practicante -->
                        <section class="flex items-start gap-4">

                            <div
                                class="w-14 h-14 shrink-0 rounded-full bg-gray-900 text-white flex items-center justify-center text-lg font-semibold"
                                x-text="initials(selectedAttendance.usuario)">
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3
                                    class="text-xl font-bold text-gray-900 leading-tight break-words"
                                    x-text="selectedAttendance.usuario || 'Usuario desconocido'">
                                </h3>

                                <p
                                    class="text-sm text-gray-500 mt-1"
                                    x-text="formatDate(selectedAttendance.fecha)">
                                </p>

                                <span
                                    class="inline-flex items-center gap-2 mt-3 px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="statusClass(selectedAttendance.tipo)">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(selectedAttendance.tipo)"></span>
                                    <span x-text="statusLabel(selectedAttendance.tipo)"></span>
                                </span>
                            </div>

                        </section>

                        <!-- Registro de horario (no aplica si faltó) -->
                        <template x-if="selectedAttendance.tipo !== 'falto'">
                            <section>
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">
                                    Registro de horario
                                </h4>

                                <div class="grid grid-cols-3 divide-x divide-gray-200 rounded-2xl border border-gray-200 bg-gray-50/60">

                                    <div class="p-4">
                                        <p class="flex items-center gap-1.5 text-xs text-gray-500">
                                            <i class="fa-solid fa-arrow-right-to-bracket text-[11px]"></i>
                                            Ingreso
                                        </p>
                                        <p
                                            class="mt-2 text-lg font-semibold tabular-nums"
                                            :class="selectedAttendance.hora_inicio ? 'text-gray-900' : 'text-gray-400 text-sm font-medium'"
                                            x-text="shortTime(selectedAttendance.hora_inicio) || 'Sin registro'">
                                        </p>
                                    </div>

                                    <div class="p-4">
                                        <p class="flex items-center gap-1.5 text-xs text-gray-500">
                                            <i class="fa-solid fa-arrow-right-from-bracket text-[11px]"></i>
                                            Salida
                                        </p>
                                        <p
                                            class="mt-2 text-lg font-semibold tabular-nums"
                                            :class="selectedAttendance.hora_fin ? 'text-gray-900' : 'text-gray-400 text-sm font-medium'"
                                            x-text="shortTime(selectedAttendance.hora_fin) || 'Pendiente'">
                                        </p>
                                    </div>

                                    <div class="p-4">
                                        <p class="flex items-center gap-1.5 text-xs text-gray-500">
                                            <i class="fa-regular fa-hourglass-half text-[11px]"></i>
                                            Trabajado
                                        </p>
                                        <p
                                            class="mt-2 text-lg font-semibold tabular-nums"
                                            :class="selectedAttendance.tiempo_trabajado ? 'text-gray-900' : 'text-gray-400 text-sm font-medium'"
                                            x-text="selectedAttendance.tiempo_trabajado || 'Sin datos'">
                                        </p>
                                    </div>

                                </div>
                            </section>
                        </template>

                        <!-- Evidencias -->
                        <section>

                            <div class="flex items-center justify-between mb-1">
                                <h4 class="text-sm font-semibold text-gray-900">
                                    Evidencias de trabajo
                                </h4>

                                <span
                                    class="text-xs font-medium text-gray-600 bg-gray-100 rounded-full px-2.5 py-0.5"
                                    x-text="(selectedAttendance.evidencias?.length || 0)">
                                </span>
                            </div>

                            <p class="text-sm text-gray-500 mb-4">
                                Archivos presentados por el practicante.
                            </p>

                            <template x-if="selectedAttendance.evidencias?.length > 0">
                                <ul class="space-y-2.5">

                                    <template
                                        x-for="evidencia in selectedAttendance.evidencias"
                                        :key="evidencia.id">

                                        <li>
                                            <a
                                                :href="evidencia.url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="group flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-gray-300 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 transition">

                                                <div
                                                    class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                                                    :class="fileStyle(evidencia).bg">
                                                    <i class="text-base" :class="fileStyle(evidencia).icon"></i>
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <p
                                                        class="text-sm font-medium text-gray-900 truncate"
                                                        x-text="evidencia.nombre">
                                                    </p>

                                                    <p
                                                        class="text-xs text-gray-500 truncate"
                                                        x-text="evidencia.descripcion || 'Sin descripción'">
                                                    </p>
                                                </div>

                                                <i class="fa-solid fa-arrow-up-right-from-square text-xs text-gray-400 group-hover:text-gray-700 shrink-0"></i>
                                            </a>
                                        </li>

                                    </template>

                                </ul>
                            </template>

                            <template x-if="!selectedAttendance.evidencias?.length">
                                <div class="rounded-xl bg-gray-50 border border-dashed border-gray-300 px-5 py-6 text-center">
                                    <i class="fa-regular fa-folder-open text-2xl text-gray-400"></i>
                                    <p class="text-sm text-gray-500 mt-2">
                                        No se han presentado evidencias.
                                    </p>
                                </div>
                            </template>

                        </section>

                        <!-- Justificación -->
                        <template x-if="selectedAttendance.tipo === 'falto'">

                            <section class="pt-8 border-t border-gray-100">

                                <h4 class="text-sm font-semibold text-gray-900 mb-1">
                                    Justificación de inasistencia
                                </h4>

                                <p class="text-sm text-gray-500 mb-4">
                                    Información presentada por el practicante.
                                </p>

                                <template x-if="selectedAttendance.justificacion">

                                    <div class="rounded-2xl border border-gray-200 overflow-hidden">

                                        <div class="p-4">
                                            <p class="text-xs font-medium text-gray-500">Motivo</p>

                                            <p
                                                class="text-sm text-gray-900 mt-1.5 leading-relaxed whitespace-pre-line"
                                                x-text="selectedAttendance.justificacion.motivo">
                                            </p>
                                        </div>

                                        <a
                                            :href="selectedAttendance.justificacion.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center justify-between gap-3 px-4 py-3 border-t border-gray-200 bg-gray-50 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-black focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-gray-400 transition">

                                            <span class="flex items-center gap-2">
                                                <i class="fa-regular fa-file-lines"></i>
                                                Ver documento de respaldo
                                            </span>

                                            <i class="fa-solid fa-arrow-up-right-from-square text-xs text-gray-400"></i>
                                        </a>

                                    </div>

                                </template>

                                <template x-if="!selectedAttendance.justificacion">

                                    <div class="rounded-xl bg-gray-50 border border-dashed border-gray-300 px-5 py-6 text-center">
                                        <i class="fa-regular fa-file-lines text-2xl text-gray-400"></i>
                                        <p class="text-sm text-gray-500 mt-2">
                                            El practicante todavía no ha presentado una justificación.
                                        </p>
                                    </div>

                                </template>

                            </section>

                        </template>

                    </div>
                </template>
            </div>

            <!-- Pie fijo -->
            <footer class="shrink-0 px-6 py-4 border-t border-gray-100 bg-white">
                <button
                    type="button"
                    @click="closeDrawer()"
                    class="w-full rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-900 transition">
                    Cerrar
                </button>
            </footer>

        </aside>

    </div>

    @push('scripts')

        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/all/global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/monarch/global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/locales/es/global.js"></script>

        <script>
            function calendarAdmin() {
                return {
                    drawerOpen: false,
                    selectedAttendance: null,
                    calendar: null,

                    init() {
                        this.calendar = new FullCalendar.Calendar(this.$refs.calendar, {

                            locale: 'es',

                            headerToolbar: {
                                left: 'prev,next today',
                                center: 'title',
                                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                            },

                            initialView: 'dayGridMonth',

                            slotMinTime: '07:00:00',
                            slotMaxTime: '13:00:00',

                            allDaySlot: false,

                            height: 'auto',

                            nowIndicator: true,

                            displayEventTime: false,
                            displayEventEnd: false,

                            dayMaxEvents: 3,

                            eventTimeFormat: {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            },

                            events: {
                                url: '{{ route('admin.calendar.data') }}',
                                method: 'GET',

                                failure: function() {
                                    alert('Hubo un error al cargar las asistencias.');
                                }
                            },

                            // Personalizar eventos en mes, semana y día
                            eventContent: (info) => {

                                const props = info.event.extendedProps;

                                const usuario = props.usuario || info.event.title || 'Sin nombre';

                                const tipo = props.tipo || 'pendiente';

                                const inicio = props.hora_inicio || '';
                                const fin = props.hora_fin || '';

                                const horario = inicio
                                    ? (inicio.substring(0, 5) + (fin ? ' - ' + fin.substring(0, 5) : ''))
                                    : 'Sin registro horario';

                                const container = document.createElement('div');
                                container.className = 'attendance-event';

                                const name = document.createElement('div');
                                name.className = 'attendance-event-name';
                                name.textContent = usuario;

                                const status = document.createElement('div');
                                status.className = 'attendance-event-status';
                                status.textContent = this.statusLabel(tipo);

                                const time = document.createElement('div');
                                time.className = 'attendance-event-detail';
                                time.textContent = horario;

                                container.appendChild(name);
                                container.appendChild(status);
                                container.appendChild(time);

                                return {
                                    domNodes: [container]
                                };
                            },

                            // Abrir drawer
                            eventClick: (info) => {

                                const props = info.event.extendedProps;

                                this.selectedAttendance = {
                                    id: info.event.id,
                                    fecha: props.fecha || info.event.startStr,
                                    usuario: props.usuario,
                                    tipo: props.tipo,
                                    hora_inicio: props.hora_inicio,
                                    hora_fin: props.hora_fin,
                                    tiempo_trabajado: props.tiempo_trabajado,
                                    evidencias: props.evidencias || [],
                                    justificacion: props.justificacion || null
                                };

                                this.drawerOpen = true;
                            }

                        });

                        this.calendar.render();
                    },

                    statusLabel(tipo) {
                        const labels = {
                            pendiente: 'Pendiente',
                            asistio: 'Asistió',
                            tardanza: 'Tardanza',
                            falto: 'Falta'
                        };

                        return labels[tipo] || tipo;
                    },

                    statusClass(tipo) {
                        const classes = {
                            pendiente: 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-200',
                            asistio: 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-200',
                            tardanza: 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-200',
                            falto: 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200'
                        };

                        return classes[tipo] || 'bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200';
                    },

                    statusDot(tipo) {
                        const classes = {
                            pendiente: 'bg-yellow-500',
                            asistio: 'bg-green-500',
                            tardanza: 'bg-orange-500',
                            falto: 'bg-red-500'
                        };

                        return classes[tipo] || 'bg-gray-400';
                    },

                    // "2025-03-14" -> "Viernes, 14 de marzo de 2025"
                    formatDate(value) {
                        if (!value) return '';

                        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
                        if (!match) return value;

                        const date = new Date(+match[1], +match[2] - 1, +match[3]);

                        const text = date.toLocaleDateString('es-PE', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });

                        return text.charAt(0).toUpperCase() + text.slice(1);
                    },

                    // "08:03:12" -> "08:03"
                    shortTime(value) {
                        return value ? String(value).substring(0, 5) : '';
                    },

                    initials(name) {
                        if (!name) return '?';

                        return name
                            .trim()
                            .split(/\s+/)
                            .slice(0, 2)
                            .map(word => word.charAt(0).toUpperCase())
                            .join('');
                    },

                    // Icono y color según el tipo de archivo
                    fileStyle(evidencia) {
                        const source = (evidencia.nombre || evidencia.url || '').split('?')[0];
                        const ext = source.includes('.') ? source.split('.').pop().toLowerCase() : '';

                        if (ext === 'pdf') {
                            return { icon: 'fa-regular fa-file-pdf text-red-600', bg: 'bg-red-50' };
                        }

                        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                            return { icon: 'fa-regular fa-file-image text-blue-600', bg: 'bg-blue-50' };
                        }

                        if (['doc', 'docx'].includes(ext)) {
                            return { icon: 'fa-regular fa-file-word text-sky-600', bg: 'bg-sky-50' };
                        }

                        if (['xls', 'xlsx', 'csv'].includes(ext)) {
                            return { icon: 'fa-regular fa-file-excel text-green-600', bg: 'bg-green-50' };
                        }

                        if (['zip', 'rar', '7z'].includes(ext)) {
                            return { icon: 'fa-regular fa-file-zipper text-amber-600', bg: 'bg-amber-50' };
                        }

                        return { icon: 'fa-regular fa-file-lines text-gray-600', bg: 'bg-gray-100' };
                    },

                    closeDrawer() {
                        this.drawerOpen = false;
                        this.selectedAttendance = null;
                    }
                };
            }
        </script>

    @endpush

</x-admin-layout>
