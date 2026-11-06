<x-admin-layout>

    <div class="space-y-6">

        {{-- Encabezado --}}
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Dashboard
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Resumen general del control de asistencia de los practicantes.
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
        <section aria-label="Indicadores del día" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

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
                        <i class="fa-solid fa-users" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            {{-- Asistencias de hoy --}}
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
                        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            {{-- Tardanzas de hoy --}}
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
                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            {{-- Faltas de hoy --}}
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
                        <i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

        </section>

        {{-- Gráficos --}}
        <section aria-label="Gráficos" class="grid grid-cols-1 gap-4 xl:grid-cols-5">

            <x-weekly-attendance
                :attendance-week="$dataDahboard['attendanceWeek']"
                class="xl:col-span-3"
            />

            <x-area-doughnut
                :areas="$dataDahboard['areas']"
                class="xl:col-span-2"
            />

        </section>

    </div>

</x-admin-layout>
