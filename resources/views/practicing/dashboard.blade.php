
<x-admin-layout>

    <div class="space-y-6">

        {{-- ENCABEZADO --}}
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Dashboard
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Resumen personal de tu asistencia y cumplimiento de prácticas.
                </p>
            </div>

            <div class="inline-flex items-center gap-2 self-start rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm sm:self-auto">
                <i class="fa-solid fa-calendar-day text-slate-400"></i>

                <span class="font-medium">
                    {{ ucfirst(now()->locale('es')->translatedFormat('l, j \d\e F \d\e Y')) }}
                </span>
            </div>
        </header>


        {{-- INDICADORES PERSONALES --}}
        <section aria-label="Indicadores personales"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Asistencias --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Asistencias del mes
                        </p>

                        <p class="mt-2 text-3xl font-semibold text-slate-900">
                            {{ $dataDahboard['assitenciaMes'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Días que registraste asistencia
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600 ring-1 ring-inset ring-green-100">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            {{-- Tardanzas --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Tardanzas del mes
                        </p>

                        <p class="mt-2 text-3xl font-semibold text-slate-900">
                            {{ $dataDahboard['tardanzasMes'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Ingresos fuera del horario
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-inset ring-amber-100">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </div>

            {{-- Faltas --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Faltas del mes
                        </p>

                        <p class="mt-2 text-3xl font-semibold text-slate-900">
                            {{ $dataDahboard['faltasMes'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Inasistencias registradas
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600 ring-1 ring-inset ring-red-100">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </div>
                </div>
            </div>

            {{-- Estado de hoy --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Estado de hoy
                        </p>

                        <p class="mt-2 text-2xl font-semibold capitalize text-slate-900">
                            @if ($dataDahboard['asistenciToday'])
                                {{ $dataDahboard['asistenciToday']->tipo->value ?? $dataDahboard['asistenciToday']->tipo }}
                            @else
                                Sin registro
                            @endif
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Estado de tu asistencia diaria
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-inset ring-blue-100">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                </div>
            </div>

        </section>


        {{-- SEGUIMIENTO DE MIS PRÁCTICAS --}}
        @if (isset($dataDahboard['seguimiento']))

            @php
                $seguimiento = $dataDahboard['seguimiento'];
                $porcentaje = min(100, max(0, $seguimiento['porcentaje']));
            @endphp

            <section aria-label="Seguimiento de mis prácticas"
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                {{-- Cabecera --}}
                <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h2 class="text-base font-semibold text-slate-900">
                            Seguimiento de mis prácticas
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Consulta tu avance y las horas que necesitas completar.
                        </p>
                    </div>

                    @switch($seguimiento['estado_seguimiento'])

                        @case('completado')
                            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700">
                                <i class="fa-solid fa-circle-check"></i>
                                Prácticas completadas
                            </span>
                            @break

                        @case('vencido')
                            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                Periodo vencido
                            </span>
                            @break

                        @default
                            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                                <i class="fa-solid fa-spinner"></i>
                                En curso
                            </span>

                    @endswitch

                </div>


                <div class="space-y-6 p-5">

                    {{-- Progreso general --}}
                    <div>

                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">

                            <div>
                                <p class="text-sm font-medium text-slate-600">
                                    Progreso de cumplimiento
                                </p>

                                <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">
                                    {{ number_format($porcentaje, 2) }}%
                                </p>
                            </div>

                            <p class="text-sm text-slate-500">
                                {{ $seguimiento['horas_acumuladas'] }}
                                de
                                {{ $seguimiento['horas_requeridas'] }} horas completadas
                            </p>

                        </div>

                        <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100">

                            <div
                                class="h-full rounded-full transition-all duration-700
                                {{ $seguimiento['estado_seguimiento'] === 'completado' ? 'bg-green-500' : ($seguimiento['estado_seguimiento'] === 'vencido' ? 'bg-red-500' : 'bg-blue-600') }}"
                                style="width: {{ $porcentaje }}%">
                            </div>

                        </div>

                    </div>


                    {{-- Resumen de horas --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <i class="fa-solid fa-hourglass-half text-blue-500"></i>
                                Horas acumuladas
                            </div>

                            <p class="mt-3 text-2xl font-semibold tabular-nums text-slate-900">
                                {{ $seguimiento['horas_acumuladas'] }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Tiempo trabajado
                            </p>
                        </div>


                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <i class="fa-solid fa-hourglass-start text-amber-500"></i>
                                Horas pendientes
                            </div>

                            <p class="mt-3 text-2xl font-semibold tabular-nums text-slate-900">
                                {{ $seguimiento['horas_pendientes'] }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Tiempo por completar
                            </p>
                        </div>


                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <i class="fa-solid fa-bullseye text-green-500"></i>
                                Meta de prácticas
                            </div>

                            <p class="mt-3 text-2xl font-semibold tabular-nums text-slate-900">
                                {{ $seguimiento['horas_requeridas'] }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Horas requeridas
                            </p>
                        </div>

                    </div>


                    {{-- Periodo de prácticas --}}
                    <div class="rounded-xl border border-slate-200 p-4">

                        <h3 class="mb-4 text-sm font-semibold text-slate-900">
                            Periodo de prácticas
                        </h3>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                    <i class="fa-solid fa-calendar-plus"></i>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-500">
                                        Fecha de inicio
                                    </p>

                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ $seguimiento['fecha_inicio'] ?? 'No definida' }}
                                    </p>
                                </div>
                            </div>


                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 text-purple-600">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>

                                <div>
                                    <p class="text-xs text-slate-500">
                                        Fecha de finalización
                                    </p>

                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ $seguimiento['fecha_fin'] ?? 'No definida' }}
                                    </p>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </section>

        @endif


        {{-- ASISTENCIA DE HOY --}}
        @if ($dataDahboard['asistenciToday'])

            @php
                $asistenciaHoy = $dataDahboard['asistenciToday'];
            @endphp

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="mb-5">
                    <h2 class="text-base font-semibold text-slate-900">
                        Mi asistencia de hoy
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Detalle de tu jornada laboral registrada.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">
                            Hora de ingreso
                        </p>

                        <p class="mt-2 text-lg font-semibold tabular-nums text-slate-900">
                            {{ $asistenciaHoy->hora_inicio ?? '--:--:--' }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">
                            Hora de salida
                        </p>

                        <p class="mt-2 text-lg font-semibold tabular-nums text-slate-900">
                            {{ $asistenciaHoy->hora_fin ?? '--:--:--' }}
                        </p>
                    </div>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-sm text-slate-500">
                            Tiempo trabajado
                        </p>

                        <p class="mt-2 text-lg font-semibold tabular-nums text-slate-900">
                            {{ $asistenciaHoy->tiempo_trabajado ?? '00:00:00' }}
                        </p>
                    </div>

                </div>

            </section>

        @endif


        {{-- GRÁFICO DE HORAS TRABAJADAS --}}
        <section aria-label="Distribución semanal de asistencia">
            <x-weekly-worked-hours
                :attendance-week="$dataDahboard['asistenciaSemana']"
            />
        </section>

    </div>

</x-admin-layout>
