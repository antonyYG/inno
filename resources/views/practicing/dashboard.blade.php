<x-admin-layout>

    <div class="space-y-6">

        {{-- Encabezado --}}
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Dashboard
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Resumen personal de tu asistencia y cumplimiento de jornada laboral.
                </p>
            </div>

            <div class="inline-flex items-center gap-2 self-start rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm sm:self-auto">
                <i class="fa-solid fa-calendar-day text-slate-400" aria-hidden="true"></i>
                <span class="font-medium">
                    {{ ucfirst(now()->locale('es')->translatedFormat('l, j \d\e F \d\e Y')) }}
                </span>
            </div>
        </header>

        {{-- Tarjetas de indicadores --}}
        <section aria-label="Indicadores personales" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Asistencias del mes --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">
                            Asistencias del mes
                        </p>

                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                            {{ $dataDahboard['assitenciaMes'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Días que registraste asistencia
                        </p>
                    </div>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600 ring-1 ring-inset ring-green-100">
                        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            {{-- Tardanzas del mes --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">
                            Tardanzas del mes
                        </p>

                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                            {{ $dataDahboard['tardanzasMes'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Ingresos fuera del horario
                        </p>
                    </div>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-inset ring-amber-100">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            {{-- Faltas del mes --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">
                            Faltas del mes
                        </p>

                        <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">
                            {{ $dataDahboard['faltasMes'] }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Inasistencias registradas
                        </p>
                    </div>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 ring-1 ring-inset ring-red-100">
                        <i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            {{-- Estado de hoy --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">
                            Estado de hoy
                        </p>

                        <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                            @if ($dataDahboard['asistenciToday'])
                                {{$dataDahboard['asistenciToday']['tipo'] }}
                            @else
                                Sin registro
                            @endif
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Estado de tu asistencia diaria
                        </p>
                    </div>

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-inset ring-blue-100">
                        <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

        </section>

        {{-- Asistencia de hoy --}}
        @if ($dataDahboard['asistenciToday'])
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-slate-900">
                        Mi asistencia de hoy
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Detalle de tu jornada laboral registrada.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                    <div>
                        <p class="text-sm text-slate-500">Hora de ingreso</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">
                            {{ $dataDahboard['asistenciToday']['hora_inicio'] }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Hora de salida</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">
                            {{ $dataDahboard['asistenciToday']['hora_fin'] }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Tiempo trabajado</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">
                            {{ $dataDahboard['asistenciToday']['tiempo_trabajado'] }}
                        </p>
                    </div>

                </div>
            </section>
        @endif

        {{-- Distribución semanal de asistencia --}}
        <section aria-label="Distribución semanal de asistencia">
            <x-weekly-attendance-doughnut
                :attendance-week="$dataDahboard['asistenciaSemana']"
            />
        </section>

    </div>

</x-admin-layout>
