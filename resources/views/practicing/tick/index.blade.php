@php
    /*
    |--------------------------------------------------------------------------
    | DATOS TEMPORALES (solo diseño visual)
    |--------------------------------------------------------------------------
    | Todo lo que sale de este bloque es ficticio. Cuando se conecte a la base
    | de datos, basta con reemplazar $historial y $diasSemana por datos reales;
    | el resto de la vista (resumen, gráfico y tabla) se calcula a partir de ahí.
    */
    $fecha = fn(string $date) => \Illuminate\Support\Carbon::parse($date)->locale('es');

    $estados = [
        'asistio' => [
            'label' => 'Asistió',
            'badge' => 'bg-green-50 text-green-700 ring-green-600/20',
            'dot' => 'bg-green-500',
        ],
        'tardanza' => [
            'label' => 'Tardanza',
            'badge' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
            'dot' => 'bg-orange-500',
        ],
        'falto' => ['label' => 'Falta', 'badge' => 'bg-red-50 text-red-700 ring-red-600/20', 'dot' => 'bg-red-500'],
        'pendiente' => [
            'label' => 'Pendiente',
            'badge' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            'dot' => 'bg-slate-400',
        ],
    ];

    // Más reciente primero
    $historial = collect([
        ['fecha' => '2026-10-30', 'ingreso' => null, 'salida' => null, 'estado' => 'pendiente'],
        ['fecha' => '2026-10-29', 'ingreso' => '07:56', 'salida' => '13:02', 'estado' => 'asistio'],
        ['fecha' => '2026-10-28', 'ingreso' => '08:27', 'salida' => '13:01', 'estado' => 'tardanza'],
        ['fecha' => '2026-10-27', 'ingreso' => '07:52', 'salida' => '13:00', 'estado' => 'asistio'],
        ['fecha' => '2026-10-26', 'ingreso' => '07:58', 'salida' => '13:03', 'estado' => 'asistio'],
        ['fecha' => '2026-10-23', 'ingreso' => null, 'salida' => null, 'estado' => 'falto'],
    ]);

    $diasSemana = ['2026-10-26', '2026-10-27', '2026-10-28', '2026-10-29', '2026-10-30'];

    // Resumen semanal (a partir de los registros de la semana)
    $enSemana = $historial->whereIn('fecha', $diasSemana);

    $resumen = [
        'asistio' => $enSemana->where('estado', 'asistio')->count(),
        'tardanza' => $enSemana->where('estado', 'tardanza')->count(),
        'falto' => $enSemana->where('estado', 'falto')->count(),
    ];

    // Datos del gráfico: horas conectadas por día, separadas por estado
    $porFecha = $historial->keyBy('fecha');
    $grafico = ['labels' => [], 'asistio' => [], 'tardanza' => [], 'falto' => []];

    foreach ($diasSemana as $dia) {
        $registro = $porFecha->get($dia);
        $horas = null;

        if ($registro && $registro['ingreso'] && $registro['salida']) {
            $horas = round(
                \Illuminate\Support\Carbon::parse("$dia {$registro['ingreso']}")->diffInMinutes(
                    \Illuminate\Support\Carbon::parse("$dia {$registro['salida']}"),
                ) / 60,
                1,
            );
        }

        $grafico['labels'][] = ucfirst($fecha($dia)->translatedFormat('D'));

        foreach (['asistio', 'tardanza', 'falto'] as $estado) {
            $esEstado = $registro && $registro['estado'] === $estado;
            $grafico[$estado][] = $esEstado ? ($estado === 'falto' ? 0 : $horas) : null;
        }
    }
@endphp

<x-admin-layout>
    <x-header>
        Marca tu Asistencia
    </x-header>
    @if (session('success'))
    <div class="rounded-lg bg-green-50 p-4 text-sm text-green-700">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
        {{ session('error') }}
    </div>
@endif

@if (session('info'))
    <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-700">
        {{ session('info') }}
    </div>
@endif

    <div class="space-y-6">

        {{-- Registro de asistencia --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Marcado --}}
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            Mi jornada laboral
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Registra tu asistencia y consulta el estado de tu jornada.
                        </p>
                    </div>

                    <span
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        Jornada disponible
                    </span>
                </div>

                <div
                    class="flex flex-col items-center rounded-xl border border-slate-200 bg-slate-50/70 px-6 py-10 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#020830] text-[#f2c94c]">
                        <i class="fa-regular fa-clock text-2xl" aria-hidden="true"></i>
                    </div>

                    <h3 class="mt-5 text-xl font-semibold text-slate-900">
                        ¡Es hora de comenzar!
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        Conéctate al canal de voz de Discord y registra tu asistencia para iniciar tu jornada.
                    </p>

                    {{-- Requisito: conexión a Discord --}}
                    <div
                        class="mt-5 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600">
                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                        Discord: <span class="font-medium text-slate-800">Conectado</span>
                    </div>

                    {{-- Botón principal (sin acción por ahora: solo diseño) --}}
                    <form action="{{ route('admin.tick.marcar') }}" method="POST">
                        @csrf

                        <button type="submit" @disabled($asistencia)
                            class="mt-5 inline-flex items-center gap-2.5 rounded-lg
        bg-[#020830] px-8 py-3.5 text-base font-semibold text-white
        shadow-sm transition-colors hover:bg-[#0a1547]
        disabled:cursor-not-allowed disabled:opacity-50">
                            <i class="fa-solid fa-fingerprint text-[#f2c94c]" aria-hidden="true"></i>

                            {{ $asistencia ? 'Asistencia registrada' : 'Marcar asistencia' }}
                        </button>
                    </form>

                    <p class="mt-4 text-xs text-slate-400">
                        Horario de ingreso: 08:00 a. m.
                    </p>
                </div>
            </section>

            {{-- Información de jornada --}}
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">
                    Información de jornada
                </h2>

                <dl class="mt-5 divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between gap-4 py-3.5 first:pt-0">
                        <dt class="text-slate-500">Estado actual</dt>
                        <dd>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $estados['pendiente']['badge'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $estados['pendiente']['dot'] }}"></span>
                                Pendiente
                            </span>
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <dt class="text-slate-500">Horario asignado</dt>
                        <dd class="font-medium text-slate-900">08:00 a. m. – 01:00 p. m.</dd>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <dt class="text-slate-500">Duración</dt>
                        <dd class="font-medium text-slate-900">5 horas</dd>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-3.5 last:pb-0">
                        <dt class="text-slate-500">Fecha</dt>
                        <dd class="font-medium text-slate-900">Viernes, 30 de octubre</dd>
                    </div>
                </dl>
            </section>
        </div>

        {{-- Resumen y gráfico semanal --}}
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 p-6 pb-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        Asistencia semanal
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Resumen de tus asistencias durante la semana.
                    </p>
                </div>

                <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-600">
                    26 – 30 oct. 2026
                </span>
            </div>

            {{-- Indicadores --}}
            <div class="grid grid-cols-3 divide-x divide-slate-100 border-y border-slate-100">
                @foreach ([['clave' => 'asistio', 'titulo' => 'Asistencias', 'icono' => 'fa-check', 'color' => 'bg-green-50 text-green-600'], ['clave' => 'tardanza', 'titulo' => 'Tardanzas', 'icono' => 'fa-clock', 'color' => 'bg-orange-50 text-orange-600'], ['clave' => 'falto', 'titulo' => 'Faltas', 'icono' => 'fa-xmark', 'color' => 'bg-red-50 text-red-600']] as $item)
                    <div class="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:gap-4 sm:px-6">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $item['color'] }}">
                            <i class="fa-solid {{ $item['icono'] }} text-sm" aria-hidden="true"></i>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-xs text-slate-500 sm:text-sm">{{ $item['titulo'] }}</p>
                            <p class="text-2xl font-semibold tracking-tight text-slate-900">
                                {{ str_pad($resumen[$item['clave']], 2, '0', STR_PAD_LEFT) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Gráfico --}}
            <div class="p-6 pt-5">
                <div class="relative h-56 sm:h-64">
                    <canvas id="attendanceWeekChart" role="img"
                        aria-label="Gráfico de barras con las horas conectadas de lunes a viernes, según el estado de asistencia"></canvas>
                </div>

                <p class="mt-3 text-xs text-slate-400">
                    Horas conectadas por día. Los días sin barra aún no tienen registro.
                </p>
            </div>
        </section>



    </div>
    @push('scripts')
        <script>
            (() => {
                const ctx = document.getElementById('attendanceWeekChart');

                if (!ctx) {
                    return;
                }

                const textColor = '#64748b';

                new Chart(ctx, {
                    type: 'bar',

                    data: {
                        labels: @json($grafico['labels']),

                        datasets: [{
                                label: 'Asistió',
                                data: @json($grafico['asistio']),
                                backgroundColor: '#22c55e',
                                hoverBackgroundColor: '#16a34a',
                            },
                            {
                                label: 'Tardanza',
                                data: @json($grafico['tardanza']),
                                backgroundColor: '#f97316',
                                hoverBackgroundColor: '#ea580c',
                            },
                            {
                                label: 'Falta',
                                data: @json($grafico['falto']),
                                backgroundColor: '#ef4444',
                                hoverBackgroundColor: '#dc2626',
                                minBarLength: 4,
                            }
                        ]
                    },

                    options: {
                        responsive: true,
                        maintainAspectRatio: false,

                        borderRadius: 6,
                        borderSkipped: false,
                        maxBarThickness: 44,
                        categoryPercentage: 0.7,

                        font: {
                            family: getComputedStyle(document.body).fontFamily,
                            size: 12,
                        },

                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',

                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    boxWidth: 8,
                                    boxHeight: 8,
                                    padding: 16,
                                    color: textColor,
                                }
                            },

                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleColor: '#f8fafc',
                                bodyColor: '#e2e8f0',
                                padding: 12,
                                cornerRadius: 8,
                                boxPadding: 4,
                                usePointStyle: true,

                                callbacks: {
                                    label: (context) =>
                                        `${context.dataset.label}: ${context.parsed.y} h`
                                }
                            }
                        },

                        scales: {
                            x: {
                                stacked: true,
                                border: {
                                    display: false
                                },
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: textColor
                                },
                            },

                            y: {
                                stacked: true,
                                beginAtZero: true,
                                suggestedMax: 6,
                                border: {
                                    display: false
                                },
                                grid: {
                                    color: '#f1f5f9'
                                },

                                ticks: {
                                    color: textColor,
                                    padding: 8,
                                    stepSize: 1,
                                    callback: (value) => `${value} h`,
                                }
                            }
                        }
                    }
                });
            })();
        </script>
    @endpush
</x-admin-layout>
