@props([
    'attendanceWeek',
])


@php
    use App\Enum\EstadoAsistenciaEnum;

    $palette = [
        'asistio' => [
            'nombre' => 'Asistencias',
            'color' => '#22c55e',
            'hover' => '#16a34a',
        ],
        'tardanza' => [
            'nombre' => 'Tardanzas',
            'color' => '#f59e0b',
            'hover' => '#d97706',
        ],
        'falto' => [
            'nombre' => 'Faltas',
            'color' => '#ef4444',
            'hover' => '#dc2626',
        ],
        'justificado' => [
            'nombre' => 'Justificaciones',
            'color' => '#3b82f6',
            'hover' => '#2563eb',
        ],
    ];

    // Normalizar los registros y convertir el Enum a string.
    $registros = collect($attendanceWeek)->map(function ($item) {
        $tipo = data_get($item, 'tipo');

        return [
            'fecha' => data_get($item, 'fecha'),
            'tipo' => $tipo instanceof \BackedEnum
                ? $tipo->value
                : $tipo,
        ];
    });

    // Una sola asistencia por fecha.
    $registros = $registros
        ->unique('fecha')
        ->values();

    // Contar registros por tipo.
    $records = $registros
        ->groupBy('tipo')
        ->map(fn ($items) => $items->count());

    $labels = [];
    $values = [];
    $colors = [];
    $hoverColors = [];

    foreach ($palette as $tipo => $config) {
        $labels[] = $config['nombre'];
        $values[] = $records->get($tipo, 0);
        $colors[] = $config['color'];
        $hoverColors[] = $config['hover'];
    }

    $total = array_sum($values);

    $chartId = 'weeklyAttendanceDoughnut-' . uniqid();
@endphp


<div
    id="{{ $chartId }}-container"
    {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}
>

    {{-- Encabezado --}}
    <div class="mb-5">
        <h3 class="text-base font-semibold text-slate-900">
            Mi asistencia durante la semana
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Distribución de tus registros de asistencia semanal
        </p>
    </div>

    @if ($total > 0)

        {{-- Gráfico --}}
        <div class="relative mx-auto h-52 w-52 sm:h-56 sm:w-56">

            <canvas
                id="{{ $chartId }}"
                role="img"
                aria-label="Gráfico de dona con la distribución semanal de tus asistencias"
            ></canvas>

            {{-- Total central --}}
            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">

                <span
                    id="{{ $chartId }}-total"
                    class="text-2xl font-semibold tracking-tight text-slate-900"
                >
                    {{ $total }}
                </span>

                <span class="text-xs text-slate-500">
                    Registros
                </span>

            </div>

        </div>

        {{-- Leyenda interactiva --}}
        <div class="mt-6 max-h-60 overflow-y-auto border-t border-slate-100 pr-1">

            <ul class="divide-y divide-slate-100">

                @foreach ($palette as $tipo => $config)

                    @php
                        $index = array_search($tipo, array_keys($palette));
                        $value = $values[$index];
                        $percentage = ($value / $total) * 100;
                    @endphp

                    <li>

                        <button
                            type="button"
                            data-attendance-legend
                            data-index="{{ $index }}"
                            aria-pressed="true"
                            title="Clic para ocultar o mostrar"
                            class="flex w-full items-center justify-between gap-3 rounded-md px-1 py-2.5 text-left text-sm transition-opacity hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
                        >

                            <span class="flex min-w-0 items-center gap-2.5">

                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    style="background-color: {{ $config['color'] }}"
                                ></span>

                                <span
                                    data-attendance-name
                                    class="truncate text-slate-700"
                                >
                                    {{ $config['nombre'] }}
                                </span>

                            </span>

                            <span class="flex shrink-0 items-center gap-3">

                                <span class="font-medium text-slate-900">
                                    {{ $value }}
                                </span>

                                <span
                                    data-attendance-percent
                                    class="w-10 text-right text-xs text-slate-500"
                                >
                                    {{ number_format($percentage, 0) }}%
                                </span>

                            </span>

                        </button>

                    </li>

                @endforeach

            </ul>

        </div>

    @else

        {{-- Sin registros --}}
        <div class="flex h-56 flex-col items-center justify-center text-center">

            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
            </div>

            <p class="mt-3 text-sm font-medium text-slate-700">
                Sin registros esta semana
            </p>

            <p class="mt-1 text-sm text-slate-500">
                Aún no hay asistencias registradas para mostrar.
            </p>

        </div>

    @endif

</div>

@if ($total > 0)

    @push('scripts')
        <script>
            (() => {

                const container = document.getElementById(
                    @json($chartId . '-container')
                );

                if (!container || typeof Chart === 'undefined') {
                    return;
                }

                const ctx = container.querySelector('canvas');

                if (!ctx) {
                    return;
                }

                const totalEl = container.querySelector(
                    '#{{ $chartId }}-total'
                );

                const buttons = container.querySelectorAll(
                    '[data-attendance-legend]'
                );

                const values = @json($values);

                const chart = new Chart(ctx, {

                    type: 'doughnut',

                    data: {
                        labels: @json($labels),

                        datasets: [
                            {
                                label: 'Registros',
                                data: values,
                                backgroundColor: @json($colors),
                                hoverBackgroundColor: @json($hoverColors),
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

                                        const percentage = values.reduce(
                                            (total, item, index) => {
                                                return chart.getDataVisibility(index)
                                                    ? total + item
                                                    : total;
                                            },
                                            0
                                        );

                                        const percent = percentage > 0
                                            ? Math.round((value / percentage) * 100)
                                            : 0;

                                        return `${label}: ${value} (${percent}%)`;
                                    }
                                }
                            }
                        }
                    }
                });

                // Actualizar total y porcentajes al interactuar con la leyenda.
                const refreshLegend = () => {

                    let visibleTotal = 0;

                    values.forEach((value, index) => {
                        if (chart.getDataVisibility(index)) {
                            visibleTotal += value;
                        }
                    });

                    totalEl.textContent = visibleTotal;

                    buttons.forEach((button) => {

                        const index = Number(button.dataset.index);
                        const visible = chart.getDataVisibility(index);

                        button.classList.toggle('opacity-40', !visible);

                        button.setAttribute(
                            'aria-pressed',
                            visible ? 'true' : 'false'
                        );

                        button.querySelector(
                            '[data-attendance-name]'
                        ).classList.toggle('line-through', !visible);

                        button.querySelector(
                            '[data-attendance-percent]'
                        ).textContent = visible && visibleTotal > 0
                            ? Math.round((values[index] / visibleTotal) * 100) + '%'
                            : '—';

                    });
                };

                buttons.forEach((button) => {

                    button.addEventListener('click', () => {

                        const index = Number(button.dataset.index);

                        chart.toggleDataVisibility(index);

                        chart.update();

                        refreshLegend();

                    });

                });

            })();
        </script>
    @endpush

@endif
