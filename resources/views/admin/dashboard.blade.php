
<x-admin-layout>

    <div class="space-y-6">

        {{-- ENCABEZADO --}}
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Dashboard
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Resumen general del control de asistencia y seguimiento de prácticas.
                </p>
            </div>

            <div class="inline-flex items-center gap-2 self-start rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm sm:self-auto">
                <i class="fa-solid fa-calendar-day text-slate-400"></i>

                <span class="font-medium">
                    {{ ucfirst(now()->locale('es')->translatedFormat('l, j \d\e F \d\e Y')) }}
                </span>
            </div>
        </header>


        {{-- INDICADORES DEL DÍA --}}
        <section aria-label="Indicadores del día"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Practicantes activos --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">
                            Practicantes activos
                        </p>

                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                            {{ $dataDahboard['pracActivos'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Registrados actualmente
                        </p>
                    </div>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-inset ring-blue-100">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>
            </div>

            {{-- Asistencias --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">
                            Asistencias de hoy
                        </p>

                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                            {{ $dataDahboard['attendaceToday'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Practicantes que asistieron
                        </p>
                    </div>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600 ring-1 ring-inset ring-green-100">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
            </div>

            {{-- Tardanzas --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">
                            Tardanzas de hoy
                        </p>

                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                            {{ $dataDahboard['tarToday'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Ingresos fuera del horario
                        </p>
                    </div>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-inset ring-amber-100">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </div>

            {{-- Faltas --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">
                            Faltas de hoy
                        </p>

                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                            {{ $dataDahboard['falToday'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Inasistencias registradas
                        </p>
                    </div>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 ring-1 ring-inset ring-red-100">
                        <i class="fa-solid fa-calendar-xmark"></i>
                    </div>
                </div>
            </div>

        </section>


        {{-- GRÁFICOS --}}
        <section aria-label="Gráficos de asistencia"
            class="grid grid-cols-1 gap-4 xl:grid-cols-5">

            <x-weekly-attendance
                :attendance-week="$dataDahboard['attendanceWeek']"
                class="xl:col-span-3"
            />

            <x-area-doughnut
                :areas="$dataDahboard['areas']"
                class="xl:col-span-2"
            />

        </section>


        {{-- SEGUIMIENTO DE PRÁCTICAS --}}
        <section aria-label="Seguimiento de prácticas"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            {{-- Cabecera --}}
            <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h2 class="text-base font-semibold text-slate-900">
                        Seguimiento de prácticas
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Control de horas acumuladas y cumplimiento de los practicantes.
                    </p>
                </div>

                <div class="inline-flex w-fit items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700">
                    <i class="fa-solid fa-chart-line"></i>
                    Progreso de prácticas
                </div>

            </div>


            {{-- Lista de practicantes --}}
            @if ($dataDahboard['seguimientoPracticantes']->isNotEmpty())

                <div class="divide-y divide-slate-100">

                    @foreach ($dataDahboard['seguimientoPracticantes'] as $practicante)

                        <article class="p-5 transition hover:bg-slate-50/70">

                            <div class="flex flex-col gap-5">

                                {{-- Información personal --}}
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                                    <div class="flex min-w-0 items-center gap-3">

                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-semibold uppercase text-slate-600">
                                            {{ substr($practicante['nombre'], 0, 1) }}{{ substr($practicante['apellidos'], 0, 1) }}
                                        </div>

                                        <div class="min-w-0">
                                            <h3 class="truncate text-sm font-semibold text-slate-900">
                                                {{ $practicante['nombre'] }}
                                                {{ $practicante['apellidos'] }}
                                            </h3>

                                            <p class="mt-1 truncate text-xs text-slate-500">
                                                {{ $practicante['email'] }}
                                            </p>
                                        </div>

                                    </div>


                                    {{-- Estado --}}
                                    <div>
                                        @switch($practicante['estado_seguimiento'])

                                            @case('completado')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Completado
                                                </span>
                                                @break

                                            @case('vencido')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700">
                                                    <i class="fa-solid fa-circle-exclamation"></i>
                                                    Vencido
                                                </span>
                                                @break

                                            @default
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                                                    <i class="fa-solid fa-spinner"></i>
                                                    En curso
                                                </span>

                                        @endswitch
                                    </div>

                                </div>


                                {{-- Fechas --}}
                                <div class="flex flex-wrap gap-x-6 gap-y-2 text-xs text-slate-500">

                                    <span>
                                        <i class="fa-regular fa-calendar-plus mr-1.5"></i>
                                        Inicio:
                                        <strong class="font-medium text-slate-700">
                                            {{ $practicante['fecha_inicio'] ?? 'No definido' }}
                                        </strong>
                                    </span>

                                    <span>
                                        <i class="fa-regular fa-calendar-check mr-1.5"></i>
                                        Fin:
                                        <strong class="font-medium text-slate-700">
                                            {{ $practicante['fecha_fin'] ?? 'No definido' }}
                                        </strong>
                                    </span>

                                </div>


                                {{-- Barra de progreso --}}
                                <div>

                                    <div class="mb-2 flex items-center justify-between gap-3">

                                        <span class="text-xs font-medium text-slate-600">
                                            Cumplimiento de horas
                                        </span>

                                        <span class="text-sm font-semibold tabular-nums text-slate-900">
                                            {{ number_format($practicante['porcentaje'], 2) }}%
                                        </span>

                                    </div>

                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">

                                        <div
                                            class="h-full rounded-full transition-all duration-500
                                            {{ $practicante['estado_seguimiento'] === 'completado' ? 'bg-green-500' : ($practicante['estado_seguimiento'] === 'vencido' ? 'bg-red-500' : 'bg-blue-600') }}"
                                            style="width: {{ min(100, max(0, $practicante['porcentaje'])) }}%">
                                        </div>

                                    </div>

                                </div>


                                {{-- Resumen de horas --}}
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                                    <div class="rounded-lg bg-slate-50 p-3">
                                        <p class="text-xs text-slate-500">
                                            Horas acumuladas
                                        </p>

                                        <p class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
                                            {{ $practicante['horas_acumuladas'] }}
                                            <span class="text-xs font-normal text-slate-500">h</span>
                                        </p>
                                    </div>

                                    <div class="rounded-lg bg-slate-50 p-3">
                                        <p class="text-xs text-slate-500">
                                            Horas pendientes
                                        </p>

                                        <p class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
                                            {{ $practicante['horas_pendientes'] }}
                                            <span class="text-xs font-normal text-slate-500">h</span>
                                        </p>
                                    </div>

                                    <div class="rounded-lg bg-slate-50 p-3">
                                        <p class="text-xs text-slate-500">
                                            Meta de prácticas
                                        </p>

                                        <p class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
                                            {{ $practicante['horas_requeridas'] }}
                                            <span class="text-xs font-normal text-slate-500">horas</span>
                                        </p>
                                    </div>

                                </div>

                            </div>

                        </article>

                    @endforeach

                </div>

            @else

                {{-- Estado vacío --}}
                <div class="px-5 py-12 text-center">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i class="fa-solid fa-users text-xl"></i>
                    </div>

                    <h3 class="mt-4 text-sm font-semibold text-slate-900">
                        No hay practicantes registrados
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        El seguimiento aparecerá aquí cuando registres practicantes.
                    </p>

                </div>

            @endif

        </section>

    </div>

</x-admin-layout>
