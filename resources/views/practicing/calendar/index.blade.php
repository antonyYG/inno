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
        </style>
    @endpush

    <div
        x-data="calendarPractitioner('{{ now()->format('Y-m-d H:i:s') }}')"
        x-effect="document.body.classList.toggle('overflow-hidden', drawerOpen)"
        class="relative">

        <!-- Mensajes del servidor -->
        @if (session('success'))
            <div class="mb-4 flex items-start gap-3 rounded-xl bg-green-50 border border-green-200 px-4 py-3">
                <i class="fa-solid fa-circle-check text-green-600 mt-0.5"></i>
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
                <i class="fa-solid fa-circle-exclamation text-red-600 mt-0.5"></i>
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 flex items-start gap-3 rounded-xl bg-red-50 border border-red-200 px-4 py-3">
                <i class="fa-solid fa-circle-exclamation text-red-600 mt-0.5"></i>
                <ul class="text-sm text-red-800 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Calendario -->
        <div x-ref="calendar"></div>

        <!-- Fondo drawer -->
        <div
            x-show="drawerOpen"
            x-cloak
            x-transition.opacity
            @click="closeDrawer()"
            class="fixed inset-0 bg-gray-900/40 backdrop-blur-[1px] z-40">
        </div>

        <!-- Drawer -->
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
                    Mi asistencia
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

                        <!-- Fecha y estado -->
                        <section class="flex items-start gap-4">

                            <div
                                class="w-14 h-14 shrink-0 rounded-2xl flex items-center justify-center text-xl"
                                :class="statusClass(selectedAttendance.tipo)">
                                <i :class="statusIcon(selectedAttendance.tipo)"></i>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-500">
                                    Fecha de asistencia
                                </p>

                                <h3
                                    class="text-xl font-bold text-gray-900 leading-tight mt-0.5"
                                    x-text="formatDate(selectedAttendance.fecha)">
                                </h3>

                                <span
                                    class="inline-flex items-center gap-2 mt-3 px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="statusClass(selectedAttendance.tipo)">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(selectedAttendance.tipo)"></span>
                                    <span x-text="statusLabel(selectedAttendance.tipo)"></span>
                                </span>
                            </div>

                        </section>

                        <!-- Mi horario (no aplica si faltó) -->
                        <template x-if="selectedAttendance.tipo !== 'falto'">
                            <section>
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">
                                    Mi horario
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

                        <!-- Mi evidencia (solo una por jornada, no aplica si faltó) -->
                        <template x-if="selectedAttendance.tipo !== 'falto'">
                            <section>

                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="text-sm font-semibold text-gray-900">
                                        Mi evidencia
                                    </h4>

                                    <span
                                        class="text-xs font-medium rounded-full px-2.5 py-0.5"
                                        :class="tieneEvidencia() ? 'text-green-700 bg-green-50' : 'text-gray-600 bg-gray-100'"
                                        x-text="tieneEvidencia() ? 'Presentada' : 'Pendiente'">
                                    </span>
                                </div>

                                <p class="text-sm text-gray-500 mb-4">
                                    Archivo que presentaste para validar tu asistencia.
                                </p>

                                <!-- Evidencia presentada -->
                                <template x-if="tieneEvidencia()">
                                    <div class="space-y-3">

                                        <a
                                            :href="evidenciaPresentada().url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="group flex items-center gap-3 p-3 rounded-xl border border-gray-200 hover:border-gray-300 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 transition">

                                            <div
                                                class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                                                :class="fileStyle(evidenciaPresentada()).bg">
                                                <i class="text-base" :class="fileStyle(evidenciaPresentada()).icon"></i>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="text-sm font-medium text-gray-900 truncate"
                                                    x-text="evidenciaPresentada().nombre">
                                                </p>

                                                <p
                                                    class="text-xs text-gray-500 truncate"
                                                    x-text="evidenciaPresentada().descripcion || 'Sin descripción'">
                                                </p>
                                            </div>

                                            <i class="fa-solid fa-arrow-up-right-from-square text-xs text-gray-400 group-hover:text-gray-700 shrink-0"></i>
                                        </a>

                                        <div class="flex items-start gap-3 rounded-xl bg-green-50 border border-green-200 px-4 py-3">
                                            <i class="fa-solid fa-circle-check text-green-600 mt-0.5"></i>
                                            <p class="text-sm text-green-800">
                                                Ya presentaste tu evidencia de esta jornada. Tu asistencia queda validada.
                                            </p>
                                        </div>

                                    </div>
                                </template>

                                <!-- Sin evidencia -->
                                <template x-if="!tieneEvidencia()">
                                    <div class="rounded-xl bg-gray-50 border border-dashed border-gray-300 px-5 py-6 text-center">
                                        <i class="fa-regular fa-folder-open text-2xl text-gray-400"></i>
                                        <p class="text-sm text-gray-500 mt-2">
                                            Todavía no has presentado tu evidencia.
                                        </p>
                                    </div>
                                </template>

                            </section>
                        </template>

                        <!-- Aún no es hora de presentar evidencia -->
                        <template x-if="selectedAttendance.tipo !== 'falto' && !tieneEvidencia() && !horarioHabilitado()">
                            <section class="pt-8 border-t border-gray-100">
                                <div class="flex items-start gap-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
                                    <i class="fa-regular fa-clock text-amber-600 mt-0.5"></i>
                                    <div>
                                        <p class="text-sm font-medium text-amber-900">
                                            Evidencia no disponible todavía
                                        </p>
                                        <p class="text-sm text-amber-800 mt-0.5">
                                            Podrás presentarla desde la 1:00 p. m., al finalizar tu jornada.
                                        </p>
                                    </div>
                                </div>
                            </section>
                        </template>

                        <!-- Presentar evidencia (solo si aún no hay ninguna y ya es la hora) -->
                        <template x-if="puedeSubirEvidencia()">

                            <section class="pt-8 border-t border-gray-100">

                                <h4 class="text-sm font-semibold text-gray-900 mb-1">
                                    Presentar evidencia
                                </h4>

                                <p class="text-sm text-gray-500 mb-4">
                                    Adjunta un único archivo que demuestre las actividades realizadas. Una vez enviado no podrás subir otro.
                                </p>

                                <form
                                    :action="evidenciaUrl()"
                                    method="POST"
                                    enctype="multipart/form-data"
                                    x-data="{ fileName: '', sending: false }"
                                    @submit="sending = true"
                                    class="space-y-5">

                                    @csrf

                                    <div>
                                        <span class="block text-sm font-medium text-gray-700 mb-2">
                                            Archivo
                                        </span>

                                        <label
                                            class="flex items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50/60 px-4 py-4 cursor-pointer hover:border-gray-400 hover:bg-gray-50 focus-within:ring-2 focus-within:ring-gray-400 transition">

                                            <div class="w-10 h-10 shrink-0 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-500">
                                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="text-sm font-medium truncate"
                                                    :class="fileName ? 'text-gray-900' : 'text-gray-700'"
                                                    x-text="fileName || 'Seleccionar archivo'">
                                                </p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    JPG, PNG o PDF · máx. 5 MB
                                                </p>
                                            </div>

                                            <input
                                                type="file"
                                                name="archivo"
                                                accept=".jpg,.jpeg,.png,.pdf"
                                                required
                                                @change="fileName = $event.target.files[0]?.name || ''"
                                                class="sr-only">
                                        </label>
                                    </div>

                                    <div>
                                        <label for="evidencia-descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                                            Descripción
                                        </label>

                                        <textarea
                                            id="evidencia-descripcion"
                                            name="descripcion"
                                            rows="3"
                                            maxlength="500"
                                            required
                                            placeholder="Describe brevemente la actividad realizada..."
                                            class="w-full rounded-xl border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:border-gray-500 focus:ring-gray-500 text-sm"></textarea>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="sending"
                                        class="w-full rounded-xl bg-gray-900 hover:bg-black text-white py-2.5 text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-900 disabled:opacity-60 disabled:cursor-not-allowed transition">

                                        <i class="mr-2" :class="sending ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-upload'"></i>
                                        <span x-text="sending ? 'Enviando...' : 'Enviar evidencia'"></span>

                                    </button>

                                </form>

                            </section>

                        </template>

                        <!-- Justificación de falta -->
                        <template x-if="selectedAttendance.tipo === 'falto'">

                            <section class="pt-8 border-t border-gray-100">

                                <h4 class="text-sm font-semibold text-gray-900 mb-1">
                                    Justificación de inasistencia
                                </h4>

                                <p class="text-sm text-gray-500 mb-4">
                                    Presenta el motivo de tu inasistencia y adjunta un documento de respaldo.
                                </p>

                                <!-- Ya enviada -->
                                <template x-if="selectedAttendance.justificacion">

                                    <div class="rounded-2xl border border-gray-200 overflow-hidden">

                                        <div class="p-4">
                                            <p class="text-xs font-medium text-gray-500">Motivo presentado</p>

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
                                                Ver documento enviado
                                            </span>

                                            <i class="fa-solid fa-arrow-up-right-from-square text-xs text-gray-400"></i>
                                        </a>

                                    </div>

                                </template>

                                <!-- Formulario -->
                                <template x-if="!selectedAttendance.justificacion">

                                    <form
                                        :action="justificacionUrl()"
                                        method="POST"
                                        enctype="multipart/form-data"
                                        x-data="{ fileName: '', sending: false }"
                                        @submit="sending = true"
                                        class="space-y-5">

                                        @csrf

                                        <div>
                                            <label for="justificacion-motivo" class="block text-sm font-medium text-gray-700 mb-2">
                                                Motivo
                                            </label>

                                            <textarea
                                                id="justificacion-motivo"
                                                name="motivo"
                                                rows="4"
                                                required
                                                placeholder="Describe el motivo de tu inasistencia..."
                                                class="w-full rounded-xl border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:border-gray-500 focus:ring-gray-500 text-sm"></textarea>
                                        </div>

                                        <div>
                                            <span class="block text-sm font-medium text-gray-700 mb-2">
                                                Documento de respaldo
                                            </span>

                                            <label
                                                class="flex items-center gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50/60 px-4 py-4 cursor-pointer hover:border-gray-400 hover:bg-gray-50 focus-within:ring-2 focus-within:ring-gray-400 transition">

                                                <div class="w-10 h-10 shrink-0 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-500">
                                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <p
                                                        class="text-sm font-medium truncate"
                                                        :class="fileName ? 'text-gray-900' : 'text-gray-700'"
                                                        x-text="fileName || 'Seleccionar archivo'">
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        JPG, PNG o PDF
                                                    </p>
                                                </div>

                                                <input
                                                    type="file"
                                                    name="evidencia"
                                                    accept=".jpg,.jpeg,.png,.pdf"
                                                    required
                                                    @change="fileName = $event.target.files[0]?.name || ''"
                                                    class="sr-only">
                                            </label>
                                        </div>

                                        <button
                                            type="submit"
                                            :disabled="sending"
                                            class="w-full rounded-xl bg-gray-900 hover:bg-black text-white py-2.5 text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-gray-900 disabled:opacity-60 disabled:cursor-not-allowed transition">

                                            <i class="mr-2" :class="sending ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-paper-plane'"></i>
                                            <span x-text="sending ? 'Enviando...' : 'Enviar justificación'"></span>

                                        </button>

                                    </form>

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
                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 transition">
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
            function calendarPractitioner(serverNow) {
                return {
                    drawerOpen: false,
                    selectedAttendance: null,

                    horaSalida: '13:00:00',   // hora desde la que se puede subir la evidencia
                    offset: 0,                // diferencia entre la hora del servidor y la del navegador
                    tick: 0,                  // fuerza a Alpine a reevaluar la hora

                    init() {

                        // Sincronizar con la hora del servidor (evita depender del reloj del dispositivo)
                        const m = String(serverNow).match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/);
                        if (m) {
                            const wall = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]);
                            this.offset = wall.getTime() - Date.now();
                        }

                        // Reevaluar cada 30 s: el formulario se habilita solo a la 1:00 p. m.
                        setInterval(() => this.tick++, 30000);

                        const calendar = new FullCalendar.Calendar(this.$refs.calendar, {

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

                                failure: function() {
                                    alert('Hubo un error al cargar tus asistencias.');
                                }
                            },

                            eventContent: (info) => {

                                const props = info.event.extendedProps;

                                const tipo = props.tipo || 'pendiente';

                                const container = document.createElement('div');
                                container.className = 'attendance-event';

                                const name = document.createElement('div');
                                name.className = 'attendance-event-name';
                                name.textContent = 'Mi asistencia';

                                const status = document.createElement('div');
                                status.className = 'attendance-event-status';
                                status.textContent = this.statusLabel(tipo);

                                const inicio = props.hora_inicio || '';
                                const fin = props.hora_fin || '';

                                const time = document.createElement('div');
                                time.className = 'attendance-event-detail';

                                time.textContent = inicio
                                    ? inicio.substring(0, 5) + (fin ? ' - ' + fin.substring(0, 5) : '')
                                    : 'Sin registro horario';

                                container.appendChild(name);
                                container.appendChild(status);
                                container.appendChild(time);

                                return {
                                    domNodes: [container]
                                };
                            },

                            eventClick: (info) => {

                                const props = info.event.extendedProps;

                                this.selectedAttendance = {
                                    id: info.event.id,
                                    fecha: props.fecha || info.event.startStr,
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

                        calendar.render();
                    },

                    evidenciaUrl() {
                        return '{{ route('admin.practicing.evidencias.store', ['asistencia' => '__ID__']) }}'
                            .replace('__ID__', this.selectedAttendance.id);
                    },

                    justificacionUrl() {
                        return '{{ route('admin.practicing.justificaciones.store', ['asistencia' => '__ID__']) }}'
                            .replace('__ID__', this.selectedAttendance.id);
                    },

                    // Hora actual según el servidor, formato "YYYY-MM-DD HH:mm:ss"
                    ahora() {
                        const d = new Date(Date.now() + this.offset);
                        const p = (n) => String(n).padStart(2, '0');

                        return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ` +
                               `${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
                    },

                    // true si ya llegó la hora de salida de la fecha de la asistencia
                    horarioHabilitado() {
                        this.tick; // dependencia reactiva para reevaluar cada 30 s

                        if (!this.selectedAttendance) return false;

                        return true;

                        // const fecha = String(this.selectedAttendance.fecha).substring(0, 10);

                        // return this.ahora() >= fecha + ' ' + this.horaSalida;
                    },

                    // Solo se permite una evidencia por jornada
                    tieneEvidencia() {
                        return (this.selectedAttendance?.evidencias?.length || 0) > 0;
                    },

                    evidenciaPresentada() {
                        return this.selectedAttendance?.evidencias?.[0] || null;
                    },

                    puedeSubirEvidencia() {
                        return !!this.selectedAttendance &&
                            this.selectedAttendance.tipo !== 'falto' &&
                            !this.tieneEvidencia() &&
                            this.horarioHabilitado();
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

                    statusIcon(tipo) {
                        const icons = {
                            pendiente: 'fa-regular fa-hourglass-half',
                            asistio: 'fa-solid fa-check',
                            tardanza: 'fa-regular fa-clock',
                            falto: 'fa-solid fa-xmark'
                        };

                        return icons[tipo] || 'fa-regular fa-calendar';
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

                    // Icono y color según el tipo de archivo
                    fileStyle(evidencia) {
                        if (!evidencia) {
                            return { icon: 'fa-regular fa-file-lines text-gray-600', bg: 'bg-gray-100' };
                        }

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
