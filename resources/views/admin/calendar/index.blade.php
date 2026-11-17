<x-admin-layout>

    @push('css')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/skeleton.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/monarch/theme.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/monarch/palettes/purple.css" rel="stylesheet">

        <style>
            [x-cloak] {
                display: none !important;
            }

            /* SweetAlert siempre por encima del drawer (9999) y del fondo (9998) */
            .swal2-container {
                z-index: 10050 !important;
            }

            .attendance-event {
                display: flex;
                flex-direction: column;
                gap: 3px;
                padding: 4px 6px;
                width: 100%;
                overflow: hidden;
                white-space: normal;
                cursor: pointer;
            }

            .attendance-event-name {
                font-size: 12px;
                font-weight: 600;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .attendance-event-status {
                font-size: 10px;
                font-weight: 600;
            }

            .attendance-event-detail {
                font-size: 11px;
                opacity: .9;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .fc .fc-daygrid-event {
                white-space: normal;
                cursor: pointer;
            }

            .fc .fc-timegrid-event {
                overflow: hidden;
                cursor: pointer;
            }

            .drawer-scroll {
                overscroll-behavior: contain;
            }
        </style>
    @endpush

    <div
        x-data="calendarAdmin()"
        x-init="init()"
        class="relative">

        {{-- CALENDARIO --}}
        <div x-ref="calendar"></div>

        {{-- FONDO --}}
        <div
            x-show="drawerOpen"
            x-cloak
            x-transition.opacity.duration.200ms
            @click="closeDrawer()"
            class="fixed inset-0 z-[9998] bg-gray-900/40 backdrop-blur-[2px]">
        </div>

        {{-- DRAWER --}}
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
            aria-label="Revisión de asistencia"
            class="fixed inset-y-0 right-0 z-[9999] flex w-full flex-col bg-white shadow-2xl sm:max-w-lg">

            {{-- HEADER --}}
            <header class="flex shrink-0 items-center justify-between border-b border-gray-100 px-6 py-5">
                <meta name="csrf-token" content="{{ csrf_token() }}">
                <div>
                    <h2 class="text-base font-bold text-gray-900">
                        Revisión de asistencia
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        Consulta y validación de registros
                    </p>
                </div>

                <button
                    type="button"
                    @click="closeDrawer()"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900">

                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </header>

            {{-- CONTENIDO --}}
            <div class="drawer-scroll flex-1 overflow-y-auto">

                <template x-if="selectedAttendance">

                    <div class="space-y-7 px-6 py-6">

                        {{-- INFORMACIÓN DEL PRACTICANTE --}}
                        <section>

                            <div class="flex items-start gap-4">

                                <div
                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gray-900 text-lg font-bold text-white"
                                    x-text="initials(selectedAttendance.usuario)">
                                </div>

                                <div class="min-w-0 flex-1">

                                    <h3
                                        class="break-words text-lg font-bold text-gray-900"
                                        x-text="selectedAttendance.usuario || 'Usuario desconocido'">
                                    </h3>

                                    <p
                                        class="mt-1 text-sm text-gray-500"
                                        x-text="formatDate(selectedAttendance.fecha)">
                                    </p>

                                    <div class="mt-3 flex flex-wrap gap-2">

                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="statusClass(selectedAttendance.tipo)">

                                            <span
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="statusDot(selectedAttendance.tipo)">
                                            </span>

                                            <span x-text="statusLabel(selectedAttendance.tipo)"></span>
                                        </span>

                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="reviewClass(selectedAttendance.estado_revision)"
                                            x-text="reviewLabel(selectedAttendance.estado_revision)">
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </section>

                        {{-- HORARIO --}}
                        <section
                            x-show="!isFalta()"
                            class="space-y-3">

                            <h4 class="text-sm font-bold text-gray-900">
                                Registro de horario
                            </h4>

                            <div class="grid grid-cols-3 divide-x divide-gray-200 rounded-2xl border border-gray-200 bg-gray-50">

                                <div class="p-3">
                                    <p class="text-xs text-gray-500">Ingreso</p>

                                    <p
                                        class="mt-2 text-base font-bold text-gray-900"
                                        x-text="shortTime(selectedAttendance.hora_inicio) || '--:--'">
                                    </p>
                                </div>

                                <div class="p-3">
                                    <p class="text-xs text-gray-500">Salida</p>

                                    <p
                                        class="mt-2 text-base font-bold text-gray-900"
                                        x-text="shortTime(selectedAttendance.hora_fin) || '--:--'">
                                    </p>
                                </div>

                                <div class="p-3">
                                    <p class="text-xs text-gray-500">Trabajado</p>

                                    <p
                                        class="mt-2 text-base font-bold text-gray-900"
                                        x-text="selectedAttendance.tiempo_trabajado || '--:--'">
                                    </p>
                                </div>

                            </div>

                        </section>

                        {{-- EVIDENCIAS --}}
                        <section
                            x-show="!isFalta()"
                            class="space-y-4">

                            <div class="flex items-center justify-between">

                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">
                                        Evidencias de trabajo
                                    </h4>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Documentos presentados por el practicante
                                    </p>
                                </div>

                                <span
                                    class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700"
                                    x-text="selectedAttendance.evidencias.length + ' archivos'">
                                </span>

                            </div>

                            <template x-if="selectedAttendance.evidencias.length > 0">

                                <div class="space-y-3">

                                    <template
                                        x-for="evidencia in selectedAttendance.evidencias"
                                        :key="evidencia.id">

                                        <div class="rounded-xl border border-gray-200 p-3 transition hover:border-gray-300">

                                            <div class="flex items-center gap-3">

                                                <div
                                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                                                    :class="fileStyle(evidencia).bg">

                                                    <i
                                                        class="text-lg"
                                                        :class="fileStyle(evidencia).icon">
                                                    </i>

                                                </div>

                                                <div class="min-w-0 flex-1">

                                                    <p
                                                        class="truncate text-sm font-semibold text-gray-900"
                                                        x-text="evidencia.nombre">
                                                    </p>

                                                    <p
                                                        class="mt-1 truncate text-xs text-gray-500"
                                                        x-text="evidencia.descripcion || 'Sin descripción'">
                                                    </p>

                                                </div>

                                                <a
                                                    :href="evidencia.url"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900"
                                                    title="Visualizar documento">

                                                    <i class="fa-solid fa-eye"></i>
                                                </a>

                                            </div>

                                        </div>

                                    </template>

                                </div>

                            </template>

                            <template x-if="selectedAttendance.evidencias.length === 0">

                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-5 py-8 text-center">

                                    <i class="fa-regular fa-folder-open text-3xl text-gray-400"></i>

                                    <p class="mt-3 text-sm font-medium text-gray-700">
                                        No hay evidencias presentadas
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500">
                                        El practicante todavía no ha adjuntado documentos.
                                    </p>

                                </div>

                            </template>

                        </section>

                        {{-- JUSTIFICACIÓN --}}
                        <section
                            x-show="isFalta() || selectedAttendance.justificaciones.length > 0"
                            class="space-y-4 border-t border-gray-100 pt-6">

                            <div class="flex items-center justify-between">

                                <div>
                                    <h4 class="text-sm font-bold text-gray-900"
                                        x-text="selectedAttendance.tipo === 'tardanza' ? 'Justificación de tardanza' : 'Justificación de inasistencia'">
                                    </h4>

                                    <p class="mt-1 text-xs text-gray-500">
                                        Información enviada para su evaluación
                                    </p>
                                </div>

                                <span
                                    x-show="selectedAttendance.justificaciones.length > 0"
                                    class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700"
                                    x-text="selectedAttendance.justificaciones.length + ' archivos'">
                                </span>

                            </div>

                            <template x-if="selectedAttendance.justificaciones.length > 0">

                                <div class="space-y-3">

                                    <template
                                        x-for="just in selectedAttendance.justificaciones"
                                        :key="just.id">

                                        <div class="overflow-hidden rounded-xl border border-gray-200">

                                            <div class="p-4">

                                                <div class="flex items-start justify-between gap-3">

                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                        Motivo presentado
                                                    </p>

                                                    <span
                                                        class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                                                        :class="reviewClass(just.estado)"
                                                        x-text="reviewLabel(just.estado)">
                                                    </span>

                                                </div>

                                                <p
                                                    class="mt-2 text-sm font-semibold text-gray-900"
                                                    x-show="just.nombre"
                                                    x-text="just.nombre">
                                                </p>

                                                <p
                                                    class="mt-1 whitespace-pre-line text-sm leading-relaxed text-gray-800"
                                                    x-text="just.motivo || 'Sin descripción'">
                                                </p>

                                            </div>

                                            <a
                                                x-show="just.url"
                                                :href="just.url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">

                                                <span class="flex items-center gap-2">
                                                    <i class="fa-regular fa-file-pdf"></i>
                                                    Ver documento de respaldo
                                                </span>

                                                <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>

                                            </a>

                                        </div>

                                    </template>

                                </div>

                            </template>

                            <template x-if="selectedAttendance.justificaciones.length === 0">

                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-5 py-8 text-center">

                                    <i class="fa-regular fa-file-circle-question text-3xl text-gray-400"></i>

                                    <p class="mt-3 text-sm font-medium text-gray-700">
                                        No hay justificación presentada
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500">
                                        El practicante todavía no ha justificado esta inasistencia.
                                    </p>

                                </div>

                            </template>

                        </section>

                        {{-- VALIDAR ASISTENCIA / FALTA --}}
                        <section
                            x-show="canReviewAttendance()"
                            class="space-y-4 rounded-2xl border border-blue-100 bg-blue-50/50 p-4">

                            <div class="flex items-start gap-3">

                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </div>

                                <div>
                                    <h4
                                        class="text-sm font-bold text-gray-900"
                                        x-text="isFalta() ? 'Validar falta' : 'Validar asistencia'">
                                    </h4>

                                    <p
                                        class="mt-1 text-xs leading-relaxed text-gray-600"
                                        x-text="isFalta()
                                            ? 'Confirma la inasistencia del practicante y registra una observación.'
                                            : 'Verifica las evidencias y registra una observación sobre el trabajo presentado.'">
                                    </p>
                                </div>

                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-semibold text-gray-700">
                                    Observación del administrador
                                    <span class="font-normal text-gray-400">(opcional)</span>
                                </label>

                                <textarea x-model="attendanceObservation" rows="3" maxlength="2000" placeholder="Escribe una observación..." class="w-full rounded-xl border-gray-300 text-sm focus:border-blue-400 focus:ring-blue-400"></textarea>

                                <div
                                    class="mt-1 text-right text-xs text-gray-400"
                                    x-text="attendanceObservation.length + '/2000'">
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="validateAttendance()"
                                :disabled="sendingReview"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-black disabled:cursor-not-allowed disabled:opacity-50">

                                <i class="fa-solid fa-check"></i>
                                <span x-text="isFalta() ? 'Confirmar falta' : 'Validar asistencia'"></span>
                            </button>

                            <button
                                type="button"
                                @click="openRejection('asistencia')"
                                :disabled="sendingReview"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-50">

                                <i class="fa-solid fa-xmark"></i>
                                <span x-text="isFalta() ? 'Rechazar falta' : 'Rechazar evidencias'"></span>
                            </button>

                            {{-- RECHAZO DE ASISTENCIA / FALTA --}}
                            <div
                                x-show="rejectionOpen && rejectionType === 'asistencia'"
                                x-cloak
                                class="space-y-3 rounded-2xl border border-red-200 bg-red-50/60 p-4">

                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">
                                        Motivo del rechazo
                                    </h4>

                                    <p
                                        class="mt-1 text-xs text-gray-600"
                                        x-text="isFalta()
                                            ? 'Explica por qué se rechaza el registro de esta falta.'
                                            : 'Explica qué debe corregir el practicante.'">
                                    </p>
                                </div>

                                <textarea x-model="rejectionReason" rows="3" maxlength="2000" placeholder="Escribe el motivo del rechazo..." class="w-full rounded-xl border-gray-300 text-sm focus:border-red-400 focus:ring-red-400"></textarea>

                                <div class="flex gap-2">

                                    <button
                                        type="button"
                                        @click="rejectRequest()"
                                        :disabled="sendingReview"
                                        class="flex-1 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">

                                        <span x-show="!sendingReview">Confirmar rechazo</span>
                                        <span x-show="sendingReview">Procesando...</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="cancelRejection()"
                                        :disabled="sendingReview"
                                        class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 disabled:opacity-50">

                                        Cancelar
                                    </button>

                                </div>

                            </div>

                        </section>

                        {{-- VALIDAR JUSTIFICACIÓN (FALTA) --}}
                        <section
                            x-show="canReviewJustification()"
                            class="space-y-4 rounded-2xl border border-purple-100 bg-purple-50/50 p-4">

                            <div class="flex items-start gap-3">

                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                                    <i class="fa-solid fa-file-circle-check"></i>
                                </div>

                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">
                                        Validar justificación
                                    </h4>

                                    <p class="mt-1 text-xs leading-relaxed text-gray-600">
                                        Revisa el motivo y registra una observación sobre la justificación presentada.
                                    </p>
                                </div>

                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-semibold text-gray-700">
                                    Observación del administrador
                                    <span class="font-normal text-gray-400">(opcional)</span>
                                </label>

                                <textarea x-model="justificationObservation" rows="3" maxlength="2000" placeholder="Escribe una observación sobre la justificación..." class="w-full rounded-xl border-gray-300 text-sm focus:border-purple-400 focus:ring-purple-400"></textarea>

                                <div
                                    class="mt-1 text-right text-xs text-gray-400"
                                    x-text="justificationObservation.length + '/2000'">
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="validateJustification()"
                                :disabled="sendingReview"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-purple-800 disabled:cursor-not-allowed disabled:opacity-50">

                                <i class="fa-solid fa-check"></i>
                                Aprobar justificación
                            </button>

                            <button
                                type="button"
                                @click="openRejection('justificacion')"
                                :disabled="sendingReview"
                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 disabled:opacity-50">

                                <i class="fa-solid fa-xmark"></i>
                                Rechazar justificación
                            </button>

                            {{-- RECHAZO DE JUSTIFICACIÓN --}}
                            <div
                                x-show="rejectionOpen && rejectionType === 'justificacion'"
                                x-cloak
                                class="space-y-3 rounded-2xl border border-red-200 bg-red-50/60 p-4">

                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">
                                        Motivo del rechazo
                                    </h4>

                                    <p class="mt-1 text-xs text-gray-600">
                                        Explica por qué no se acepta la justificación.
                                    </p>
                                </div>

                                <textarea x-model="rejectionReason" rows="3" maxlength="2000" placeholder="Escribe el motivo del rechazo..." class="w-full rounded-xl border-gray-300 text-sm focus:border-red-400 focus:ring-red-400"></textarea>

                                <div class="flex gap-2">

                                    <button
                                        type="button"
                                        @click="rejectRequest()"
                                        :disabled="sendingReview"
                                        class="flex-1 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50">

                                        <span x-show="!sendingReview">Confirmar rechazo</span>
                                        <span x-show="sendingReview">Procesando...</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="cancelRejection()"
                                        :disabled="sendingReview"
                                        class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 disabled:opacity-50">

                                        Cancelar
                                    </button>

                                </div>

                            </div>

                        </section>

                        {{-- HISTORIAL --}}
                        <section
                            x-show="selectedAttendance.validado_por || selectedAttendance.validado_en"
                            class="border-t border-gray-100 pt-5">

                            <h4 class="text-sm font-bold text-gray-900">
                                Historial de revisión
                            </h4>

                            <div class="mt-3 rounded-xl bg-gray-50 p-4">

                                <p class="text-xs text-gray-500">
                                    Administrador responsable
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-gray-900"
                                    x-text="selectedAttendance.validado_por || 'No disponible'">
                                </p>

                                <p
                                    class="mt-2 text-xs text-gray-500"
                                    x-text="selectedAttendance.validado_en || ''">
                                </p>

                            </div>

                        </section>

                    </div>

                </template>

            </div>

            {{-- FOOTER --}}
            <footer class="shrink-0 border-t border-gray-100 bg-white px-6 py-4">

                <button
                    type="button"
                    @click="closeDrawer()"
                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">

                    Cerrar detalle
                </button>

            </footer>

        </aside>

    </div>

    @push('scripts')

        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/all/global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/themes/monarch/global.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@7.1.0/locales/es/global.js"></script>

        <script>
            window.calendarAdmin = function () {

                return {

                    drawerOpen: false,
                    selectedAttendance: null,
                    calendar: null,

                    rejectionOpen: false,
                    rejectionType: null,
                    rejectionReason: '',

                    attendanceObservation: '',
                    justificationObservation: '',

                    sendingReview: false,

                    // Registros ya procesados en esta sesión: { 'a-12': 'aprobado', 'j-5': 'rechazado' }
                    // Sirve porque el servicio no envía el estado de revisión.
                    reviewed: {},

                    init() {

                        this.$nextTick(() => {

                            if (!this.$refs.calendar) {
                                console.error('No se encontró el elemento del calendario.');
                                return;
                            }

                            if (typeof FullCalendar === 'undefined') {
                                console.error('FullCalendar no está cargado.');
                                return;
                            }

                            this.calendar = new FullCalendar.Calendar(
                                this.$refs.calendar,
                                {

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

                                    events: {
                                        url: '{{ route('admin.calendar.data') }}',
                                        method: 'GET',

                                        failure: function (error) {
                                            console.error('Error cargando eventos:', error);
                                            alert('Error al cargar las asistencias.');
                                        }
                                    },

                                    eventContent: (info) => {

                                        const props = info.event.extendedProps;

                                        const container = document.createElement('div');
                                        container.className = 'attendance-event';

                                        const name = document.createElement('div');
                                        name.className = 'attendance-event-name';
                                        name.textContent = props.usuario || info.event.title || 'Sin nombre';

                                        const status = document.createElement('div');
                                        status.className = 'attendance-event-status';
                                        status.textContent = this.statusLabel(props.tipo);

                                        const time = document.createElement('div');
                                        time.className = 'attendance-event-detail';
                                        time.textContent = props.hora_inicio
                                            ? props.hora_inicio.substring(0, 5)
                                            : 'Sin registro horario';

                                        const estado = this.buildAttendance(info.event).estado_revision;

                                        const review = document.createElement('div');
                                        review.className = 'attendance-event-detail';
                                        review.style.fontWeight = '600';
                                        review.textContent = {
                                            aprobado: '✔ Validado',
                                            aprobada: '✔ Aprobada',
                                            rechazado: '✖ Rechazado',
                                            rechazada: '✖ Rechazada'
                                        }[estado] || '• Por revisar';

                                        container.append(name, status, time, review);

                                        return {
                                            domNodes: [container]
                                        };
                                    },

                                    eventClick: (info) => {

                                        info.jsEvent.preventDefault();

                                        this.selectedAttendance = this.buildAttendance(info.event);

                                        this.resetForms();

                                        this.drawerOpen = true;
                                    }

                                }
                            );

                            this.calendar.render();

                        });
                    },

                    /**
                     * Convierte un evento del calendario en el objeto que usa el drawer.
                     * Lee exactamente lo que envía CalendarService:
                     * evidencias[] y justificaciones[] (id, nombre, descripcion, url).
                     */
                    buildAttendance(event) {

                        const props = event.extendedProps;

                        const evidencias = Array.isArray(props.evidencias)
                            ? props.evidencias
                            : [];

                        // Más reciente primero (mayor id)
                        const justificaciones = (Array.isArray(props.justificaciones)
                            ? props.justificaciones
                            : [])
                            .map(j => ({
                                id: j.id,
                                nombre: j.nombre || '',
                                motivo: j.descripcion || j.motivo || '',
                                url: j.url || null,
                                estado: this.resolveState(
                                    'j-' + j.id,
                                    j.estado || j.estado_revision
                                )
                            }))
                            .sort((a, b) => Number(b.id) - Number(a.id));

                        const tipo = props.tipo || 'pendiente';

                        let estado = this.resolveState(
                            'a-' + event.id,
                            props.estado_revision || props.estado
                        );

                        // En una falta con justificación, manda el estado de la justificación
                        if (tipo === 'falto' && justificaciones.length > 0) {
                            estado = justificaciones[0].estado;
                        }

                        return {

                            id: event.id,

                            fecha: props.fecha || event.startStr,

                            usuario: props.usuario || 'Usuario desconocido',

                            tipo: tipo,

                            estado_revision: estado,

                            hora_inicio: props.hora_inicio || null,

                            hora_fin: props.hora_fin || null,

                            tiempo_trabajado: props.tiempo_trabajado
                                || this.workedTime(props.hora_inicio, props.hora_fin),

                            evidencias: evidencias,

                            justificaciones: justificaciones,

                            validado_por: props.validado_por || null,

                            validado_en: props.validado_en || null
                        };
                    },

                    resolveState(key, raw) {
                        return this.reviewed[key] || raw || 'pendiente';
                    },

                    workedTime(inicio, fin) {

                        if (!inicio || !fin) return null;

                        const toMinutes = (t) => {
                            const [h, m] = String(t).substring(0, 5).split(':').map(Number);
                            return (h * 60) + m;
                        };

                        const diff = toMinutes(fin) - toMinutes(inicio);

                        if (isNaN(diff) || diff < 0) return null;

                        return String(Math.floor(diff / 60)).padStart(2, '0')
                            + ':'
                            + String(diff % 60).padStart(2, '0');
                    },

                    resetForms() {

                        this.rejectionOpen = false;
                        this.rejectionType = null;
                        this.rejectionReason = '';

                        this.attendanceObservation = '';
                        this.justificationObservation = '';
                    },

                    isFalta() {
                        return !!(this.selectedAttendance && this.selectedAttendance.tipo === 'falto');
                    },

                    // Justificación a revisar: la más reciente
                    reviewTarget() {

                        if (!this.selectedAttendance) return null;

                        return this.selectedAttendance.justificaciones[0] || null;
                    },

                    canReviewAttendance() {

                        const a = this.selectedAttendance;

                        if (!a || a.estado_revision !== 'pendiente') return false;

                        // Falta: se valida/rechaza directo solo si NO hay justificación
                        if (a.tipo === 'falto') {
                            return a.justificaciones.length === 0;
                        }

                        return a.evidencias.length > 0;
                    },

                    canReviewJustification() {

                        const target = this.reviewTarget();

                        return !!(target && target.estado === 'pendiente');
                    },

                    statusLabel(tipo) {

                        const labels = {
                            pendiente: 'Pendiente',
                            asistio: 'Asistió',
                            tardanza: 'Tardanza',
                            falto: 'Falta',
                            justificado: 'Justificado'
                        };

                        return labels[tipo] || tipo || 'Sin estado';
                    },

                    statusClass(tipo) {

                        const classes = {
                            pendiente: 'bg-yellow-50 text-yellow-700',
                            asistio: 'bg-green-50 text-green-700',
                            tardanza: 'bg-orange-50 text-orange-700',
                            falto: 'bg-red-50 text-red-700',
                            justificado: 'bg-purple-50 text-purple-700'
                        };

                        return classes[tipo] || 'bg-gray-100 text-gray-700';
                    },

                    statusDot(tipo) {

                        const classes = {
                            pendiente: 'bg-yellow-500',
                            asistio: 'bg-green-500',
                            tardanza: 'bg-orange-500',
                            falto: 'bg-red-500',
                            justificado: 'bg-purple-500'
                        };

                        return classes[tipo] || 'bg-gray-400';
                    },

                    reviewLabel(estado) {

                        const labels = {
                            pendiente: 'Pendiente de revisión',
                            aprobado: 'Validado',
                            aprobada: 'Aprobada',
                            rechazada: 'Rechazada',
                            rechazado: 'Rechazado'
                        };

                        return labels[estado] || 'Sin revisión';
                    },

                    reviewClass(estado) {

                        const classes = {
                            pendiente: 'bg-amber-50 text-amber-700',
                            aprobado: 'bg-green-50 text-green-700',
                            aprobada: 'bg-green-50 text-green-700',
                            rechazado: 'bg-red-50 text-red-700',
                            rechazada: 'bg-red-50 text-red-700'
                        };

                        return classes[estado] || 'bg-gray-100 text-gray-600';
                    },

                    formatDate(value) {

                        if (!value) return '';

                        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);

                        if (!match) return value;

                        const date = new Date(
                            Number(match[1]),
                            Number(match[2]) - 1,
                            Number(match[3])
                        );

                        const result = date.toLocaleDateString('es-PE', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });

                        return result.charAt(0).toUpperCase() + result.slice(1);
                    },

                    shortTime(value) {
                        return value ? String(value).substring(0, 5) : '';
                    },

                    initials(name) {

                        if (!name) return '?';

                        return name.trim()
                            .split(/\s+/)
                            .slice(0, 2)
                            .map(word => word.charAt(0).toUpperCase())
                            .join('');
                    },

                    fileStyle(evidencia) {

                        const source = (evidencia.nombre || evidencia.url || '')
                            .split('?')[0];

                        const ext = source.includes('.')
                            ? source.split('.').pop().toLowerCase()
                            : '';

                        if (ext === 'pdf') {
                            return {
                                icon: 'fa-regular fa-file-pdf text-red-600',
                                bg: 'bg-red-50'
                            };
                        }

                        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                            return {
                                icon: 'fa-regular fa-file-image text-blue-600',
                                bg: 'bg-blue-50'
                            };
                        }

                        if (['doc', 'docx'].includes(ext)) {
                            return {
                                icon: 'fa-regular fa-file-word text-sky-600',
                                bg: 'bg-sky-50'
                            };
                        }

                        if (['xls', 'xlsx', 'csv'].includes(ext)) {
                            return {
                                icon: 'fa-regular fa-file-excel text-green-600',
                                bg: 'bg-green-50'
                            };
                        }

                        return {
                            icon: 'fa-regular fa-file-lines text-gray-600',
                            bg: 'bg-gray-100'
                        };
                    },

                    openRejection(type) {

                        this.rejectionType = type;
                        this.rejectionReason = '';
                        this.rejectionOpen = true;
                    },

                    cancelRejection() {

                        this.rejectionOpen = false;
                        this.rejectionType = null;
                        this.rejectionReason = '';
                    },

                    validateAttendance() {

                        this.sendReview(
                            '{{ route('admin.asistencias.validar', ':id') }}',
                            'asistencia',
                            {
                                observacion: this.attendanceObservation.trim() || null
                            },
                            'aprobado'
                        );
                    },

                    validateJustification() {

                        this.sendReview(
                            '{{ route('admin.justificaciones.validar', ':id') }}',
                            'justificacion',
                            {
                                observacion: this.justificationObservation.trim() || null
                            },
                            'aprobado'
                        );
                    },

                    rejectRequest() {

                        if (!this.rejectionReason.trim()) {

                            Swal.fire({
                                icon: 'warning',
                                title: 'Motivo requerido',
                                text: 'Debes indicar el motivo del rechazo.'
                            });

                            return;
                        }

                        const route = this.rejectionType === 'asistencia'
                            ? '{{ route('admin.asistencias.rechazar', ':id') }}'
                            : '{{ route('admin.justificaciones.rechazar', ':id') }}';

                        this.sendReview(
                            route,
                            this.rejectionType,
                            {
                                observacion: this.rejectionReason.trim()
                            },
                            'rechazado'
                        );
                    },

                    async sendReview(route, type, extraData, resultState) {

                        if (this.sendingReview || !this.selectedAttendance) {
                            return;
                        }

                        // Guardar el id ANTES de abrir el Swal (el drawer podría cerrarse mientras tanto)
                        const id = this.selectedAttendance.id;

                        if (!id) {

                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se encontró el identificador del registro.'
                            });

                            return;
                        }

                        const confirmation = await Swal.fire({

                            title: '¿Confirmar acción?',

                            text: 'Se registrará la decisión y la observación del administrador.',

                            icon: 'question',

                            showCancelButton: true,

                            confirmButtonText: 'Confirmar',

                            cancelButtonText: 'Cancelar',

                            confirmButtonColor: '#111827'

                        });

                        if (!confirmation.isConfirmed) return;

                        const url = route.replace(':id', encodeURIComponent(id));

                        this.sendingReview = true;

                        try {

                            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                            if (!csrf) {
                                throw new Error('No se encontró el token CSRF.');
                            }

                            const response = await fetch(url, {

                                method: 'POST',

                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf
                                },

                                body: JSON.stringify(extraData)

                            });

                            const result = await response.json();

                            if (!response.ok) {

                                const validationMessage = result.errors
                                    ? Object.values(result.errors).flat().join('\n')
                                    : null;

                                throw new Error(
                                    validationMessage ||
                                    result.message ||
                                    'No se pudo completar la operación.'
                                );
                            }

                            // Recordar que ya se procesó (para mostrar Validado / Rechazado)
                            const key = (type === 'asistencia' ? 'a-' : 'j-') + id;
                            this.reviewed[key] = resultState;

                            this.sendingReview = false;

                            this.closeDrawer();

                            if (this.calendar) {
                                this.calendar.refetchEvents();
                            }

                            await Swal.fire({

                                icon: 'success',

                                title: 'Operación realizada',

                                text: result.message || 'La revisión fue registrada correctamente.',

                                confirmButtonColor: '#111827'

                            });

                        } catch (error) {

                            console.error(error);

                            Swal.fire({

                                icon: 'error',

                                title: 'Error',

                                text: error.message || 'Ocurrió un error inesperado.'

                            });

                        } finally {

                            this.sendingReview = false;

                        }
                    },

                    closeDrawer() {

                        if (this.sendingReview) return;

                        this.drawerOpen = false;

                        this.selectedAttendance = null;

                        this.resetForms();
                    }

                };

            };

        </script>

    @endpush

</x-admin-layout>