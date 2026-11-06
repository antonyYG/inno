@props([
    'areas',
])

@php
    $palette = [
        '#3b82f6',
        '#8b5cf6',
        '#14b8a6',
        '#f59e0b',
        '#ec4899',
        '#64748b',
    ];

    $total = (int) $areas->sum('users_count');

    // El color de cada área se asigna por posición y se repite si hay más áreas que colores.
    $colors = $areas->values()->map(
        fn ($area, $index) => $palette[$index % count($palette)]
    )->all();
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>

    {{-- Encabezado --}}
    <div class="mb-5">
        <h3 class="text-base font-semibold text-slate-900">
            Practicantes por área
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Distribución de practicantes por departamento
        </p>
    </div>

    @if ($total > 0)

        {{-- Gráfico --}}
        <div class="relative mx-auto h-52 w-52 sm:h-56 sm:w-56">
            <canvas id="areaDoughnutChart" role="img" aria-label="Gráfico de dona con la distribución de practicantes por área"></canvas>

            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                <span id="areaDoughnutTotal" class="text-2xl font-semibold tracking-tight text-slate-900">
                    {{ $total }}
                </span>
                <span class="text-xs text-slate-500">
                    Total
                </span>
            </div>
        </div>

        {{-- Leyenda interactiva (con scroll si hay muchas áreas) --}}
        <div class="mt-6 max-h-60 overflow-y-auto border-t border-slate-100 pr-1">
            <ul class="divide-y divide-slate-100">
                @foreach ($areas->values() as $index => $area)
                    <li>
                        <button
                            type="button"
                            data-area-legend
                            data-index="{{ $index }}"
                            aria-pressed="true"
                            title="Clic para ocultar o mostrar"
                            class="flex w-full items-center justify-between gap-3 rounded-md px-1 py-2.5 text-left text-sm transition-opacity hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
                        >
                            <span class="flex min-w-0 items-center gap-2.5">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $colors[$index] }}"></span>
                                <span data-area-name class="truncate text-slate-700">{{ $area->nombre }}</span>
                            </span>

                            <span class="flex shrink-0 items-center gap-3">
                                <span class="font-medium text-slate-900">{{ $area->users_count }}</span>
                                <span data-area-percent class="w-10 text-right text-xs text-slate-500">
                                    {{ number_format(($area->users_count / $total) * 100, 0) }}%
                                </span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

    @else

        <div class="flex h-56 flex-col items-center justify-center text-center">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
            </div>
            <p class="mt-3 text-sm font-medium text-slate-700">Sin practicantes asignados</p>
            <p class="mt-1 text-sm text-slate-500">Aún no hay practicantes registrados en las áreas.</p>
        </div>

    @endif

</div>

@if ($total > 0)
    @push('scripts')
        <script>
            (() => {
                const ctx = document.getElementById('areaDoughnutChart');

                if (!ctx) {
                    return;
                }

                const chart = new Chart(ctx, {
                    type: 'doughnut',

                    data: {
                        labels: @json($areas->pluck('nombre')->values()),

                        datasets: [
                            {
                                label: 'Practicantes',

                                data: @json($areas->pluck('users_count')->values()),

                                backgroundColor: @json($colors),

                                borderColor: '#ffffff',
                                borderWidth: 3,
                                hoverOffset: 4,
                            }
                        ]
                    },

                    options: {
                        responsive: true,
                        maintainAspectRatio: false,

                        cutout: '72%',

                        font: {
                            family: getComputedStyle(document.body).fontFamily,
                            size: 12,
                        },

                        plugins: {
                            // La leyenda es HTML (ver más abajo) para poder hacer scroll
                            legend: {
                                display: false,
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
                                    label: function (context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;

                                        return `${label}: ${value} practicantes`;
                                    }
                                }
                            }
                        }
                    }
                });
                const totalEl = document.getElementById('areaDoughnutTotal');
                const buttons = document.querySelectorAll('[data-area-legend]');
                const values = chart.data.datasets[0].data.map(Number);

                const refreshLegend = () => {
                    let visibleTotal = 0;

                    values.forEach((value, i) => {
                        if (chart.getDataVisibility(i)) {
                            visibleTotal += value;
                        }
                    });

                    totalEl.textContent = visibleTotal;

                    buttons.forEach((button) => {
                        const i = Number(button.dataset.index);
                        const visible = chart.getDataVisibility(i);

                        button.classList.toggle('opacity-40', !visible);
                        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
                        button.querySelector('[data-area-name]').classList.toggle('line-through', !visible);

                        button.querySelector('[data-area-percent]').textContent =
                            visible && visibleTotal > 0
                                ? Math.round((values[i] / visibleTotal) * 100) + '%'
                                : '—';
                    });
                };

                buttons.forEach((button) => {
                    button.addEventListener('click', () => {
                        chart.toggleDataVisibility(Number(button.dataset.index));
                        chart.update();
                        refreshLegend();
                    });
                });
            })();
        </script>
    @endpush
@endif
