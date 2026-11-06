@props([
    'attendanceWeek',
])

@php
    $startWeek = today()->startOfWeek();

    $labels = [];
    $asistencias = [];
    $tardanzas = [];
    $faltas = [];
    $justificaciones = [];

    for ($i = 0; $i < 5; $i++) {
        $date = $startWeek->copy()->addDays($i);

        $labels[] = ucfirst($date->translatedFormat('D'));

        $records = collect($attendanceWeek)->where(
            'fecha',
            $date->toDateString()
        );

        $asistencias[] = (int) $records
            ->where('tipo', 'asistio')
            ->sum('total');

        $tardanzas[] = (int) $records
            ->where('tipo', 'tardanza')
            ->sum('total');

        $faltas[] = (int) $records
            ->where('tipo', 'falto')
            ->sum('total');

        $justificaciones[] = (int) $records
            ->where('tipo', 'justificado')
            ->sum('total');
    }

    $chartId = 'weeklyAttendanceChart-' . uniqid();
@endphp

<div {{ $attributes->merge(['class' => 'w-full min-w-0 rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>

    {{-- Encabezado --}}
    <div class="mb-5">
        <h3 class="text-base font-semibold text-slate-900">
            Asistencias durante la semana
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Resumen de asistencias, tardanzas, faltas y justificaciones
        </p>
    </div>

    {{-- Gráfico --}}
    <div class="relative h-64 sm:h-80">
        <canvas
            id="{{ $chartId }}"
            role="img"
            aria-label="Gráfico semanal de asistencias, tardanzas, faltas y justificaciones"
        ></canvas>
    </div>

</div>

@push('scripts')
    <script>
        (() => {
            const ctx = document.getElementById(@json($chartId));

            if (!ctx || typeof Chart === 'undefined') {
                return;
            }

            const textColor = '#64748b';
            const gridColor = '#f1f5f9';

            new Chart(ctx, {
                type: 'bar',

                data: {
                    labels: @json($labels),

                    datasets: [
                        {
                            label: 'Asistencias',
                            data: @json($asistencias),
                            backgroundColor: '#22c55e',
                            hoverBackgroundColor: '#16a34a',
                        },
                        {
                            label: 'Tardanzas',
                            data: @json($tardanzas),
                            backgroundColor: '#f59e0b',
                            hoverBackgroundColor: '#d97706',
                        },
                        {
                            label: 'Faltas',
                            data: @json($faltas),
                            backgroundColor: '#ef4444',
                            hoverBackgroundColor: '#dc2626',
                        },
                        {
                            label: 'Justificaciones',
                            data: @json($justificaciones),
                            backgroundColor: '#3b82f6',
                            hoverBackgroundColor: '#2563eb',
                        }
                    ]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    borderRadius: 4,
                    borderSkipped: false,
                    maxBarThickness: 26,
                    categoryPercentage: 0.7,
                    barPercentage: 0.9,

                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },

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
                                    `${context.dataset.label}: ${context.parsed.y}`
                            }
                        }
                    },

                    scales: {
                        y: {
                            beginAtZero: true,

                            border: {
                                display: false,
                            },

                            grid: {
                                color: gridColor,
                            },

                            ticks: {
                                precision: 0,
                                color: textColor,
                                padding: 8,
                            }
                        },

                        x: {
                            border: {
                                display: false,
                            },

                            grid: {
                                display: false,
                            },

                            ticks: {
                                color: textColor,
                            }
                        }
                    }
                }
            });
        })();
    </script>
@endpush
