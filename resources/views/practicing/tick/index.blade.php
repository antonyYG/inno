
@php
    use App\Enum\EstadoAsistenciaEnum;

    $estados = [
        EstadoAsistenciaEnum::ASISTIO->value => [
            'label' => 'Asistió',
            'badge' => 'bg-green-50 text-green-700 ring-green-600/20',
            'dot' => 'bg-green-500',
        ],
        EstadoAsistenciaEnum::TARDANZA->value => [
            'label' => 'Tardanza',
            'badge' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
            'dot' => 'bg-orange-500',
        ],
        EstadoAsistenciaEnum::FALTO->value => [
            'label' => 'Falta',
            'badge' => 'bg-red-50 text-red-700 ring-red-600/20',
            'dot' => 'bg-red-500',
        ],
        EstadoAsistenciaEnum::JUSTIFICADO->value => [
            'label' => 'Justificado',
            'badge' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'dot' => 'bg-blue-500',
        ],
        EstadoAsistenciaEnum::PENDIENTE->value => [
            'label' => 'Pendiente',
            'badge' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            'dot' => 'bg-slate-400',
        ],
    ];

    // Datos devueltos por el servicio
    $asistenciaActual = $asistencia['asistencia'] ?? null;

    $asistenciaSemana = collect(
        $asistencia['asistenciaSemana'] ?? []
    );

    // Convertir Enum a string
    $estadoActual = $asistenciaActual?->tipo?->value
        ?? EstadoAsistenciaEnum::PENDIENTE->value;

    $inicioSemana = today()->startOfWeek();
    $finSemana = today()->endOfWeek();

    // Una sola asistencia por fecha
    $registrosSemana = $asistenciaSemana
        ->unique('fecha')
        ->values();

    // Obtener el valor del Enum de cada registro
    $resumen = [
        'asistio' => $registrosSemana->filter(
            fn ($registro) => $registro->tipo?->value === EstadoAsistenciaEnum::ASISTIO->value
        )->count(),

        'tardanza' => $registrosSemana->filter(
            fn ($registro) => $registro->tipo?->value === EstadoAsistenciaEnum::TARDANZA->value
        )->count(),

        'falto' => $registrosSemana->filter(
            fn ($registro) => $registro->tipo?->value === EstadoAsistenciaEnum::FALTO->value
        )->count(),
    ];
@endphp

<x-admin-layout>

    <x-header>
        Marca tu Asistencia
    </x-header>

    {{-- Mensajes --}}
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

                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        Jornada disponible
                    </span>
                </div>

                <div class="flex flex-col items-center rounded-xl border border-slate-200 bg-slate-50/70 px-6 py-10 text-center">

                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#020830] text-[#f2c94c]">
                        <i class="fa-regular fa-clock text-2xl" aria-hidden="true"></i>
                    </div>

                    <h3 class="mt-5 text-xl font-semibold text-slate-900">
                        {{ $asistenciaActual ? '¡Jornada iniciada!' : '¡Es hora de comenzar!' }}
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">
                        Conéctate al canal de voz de Discord y registra tu asistencia para iniciar tu jornada.
                    </p>

                    <div class="mt-5 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs text-slate-600">
                        <span class="h-2 w-2 rounded-full bg-green-500"></span>
                        Discord:
                        <span class="font-medium text-slate-800">Conectado</span>
                    </div>

                    <form action="{{ route('admin.tick.marcar') }}" method="POST">
                        @csrf

                        <button
                            type="submit"
                            @disabled($asistenciaActual)
                            class="mt-5 inline-flex items-center gap-2.5 rounded-lg bg-[#020830] px-8 py-3.5 text-base font-semibold text-white shadow-sm transition-colors hover:bg-[#0a1547] disabled:cursor-not-allowed disabled:opacity-50">

                            <i class="fa-solid fa-fingerprint text-[#f2c94c]" aria-hidden="true"></i>

                            {{ $asistenciaActual ? 'Asistencia registrada' : 'Marcar asistencia' }}
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
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $estados[$estadoActual]['badge'] ?? $estados['pendiente']['badge'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $estados[$estadoActual]['dot'] ?? $estados['pendiente']['dot'] }}"></span>

                                {{ $estados[$estadoActual]['label'] ?? 'Pendiente' }}
                            </span>
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <dt class="text-slate-500">Horario asignado</dt>
                        <dd class="font-medium text-slate-900">
                            08:00 a. m. – 01:00 p. m.
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-3.5">
                        <dt class="text-slate-500">Duración</dt>
                        <dd class="font-medium text-slate-900">
                            5 horas
                        </dd>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-3.5 last:pb-0">
                        <dt class="text-slate-500">Fecha</dt>

                        <dd class="font-medium text-slate-900">
                            {{ today()->locale('es')->translatedFormat('l, d \d\e F') }}
                        </dd>
                    </div>

                    @if ($asistenciaActual)
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <dt class="text-slate-500">Hora de ingreso</dt>

                            <dd class="font-medium text-slate-900">
                                {{ \Illuminate\Support\Carbon::parse($asistenciaActual->hora_inicio)->format('h:i A') }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <dt class="text-slate-500">Tiempo trabajado</dt>

                            <dd class="font-medium text-slate-900">
                                {{ $asistenciaActual->tiempo_trabajado }}
                            </dd>
                        </div>
                    @endif

                </dl>
            </section>
        </div>

        {{-- Resumen semanal --}}
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
                    {{ $inicioSemana->format('d') }} – {{ $finSemana->format('d') }}
                    {{ $finSemana->locale('es')->translatedFormat('M Y') }}
                </span>
            </div>

            {{-- Indicadores --}}
            <div class="grid grid-cols-3 divide-x divide-slate-100 border-y border-slate-100">

                @foreach ([
                    [
                        'clave' => 'asistio',
                        'titulo' => 'Asistencias',
                        'icono' => 'fa-check',
                        'color' => 'bg-green-50 text-green-600',
                    ],
                    [
                        'clave' => 'tardanza',
                        'titulo' => 'Tardanzas',
                        'icono' => 'fa-clock',
                        'color' => 'bg-orange-50 text-orange-600',
                    ],
                    [
                        'clave' => 'falto',
                        'titulo' => 'Faltas',
                        'icono' => 'fa-xmark',
                        'color' => 'bg-red-50 text-red-600',
                    ],
                ] as $item)

                    <div class="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:gap-4 sm:px-6">

                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $item['color'] }}">
                            <i class="fa-solid {{ $item['icono'] }} text-sm" aria-hidden="true"></i>
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-xs text-slate-500 sm:text-sm">
                                {{ $item['titulo'] }}
                            </p>

                            <p class="text-2xl font-semibold tracking-tight text-slate-900">
                                {{ str_pad($resumen[$item['clave']], 2, '0', STR_PAD_LEFT) }}
                            </p>
                        </div>

                    </div>

                @endforeach

            </div>

            {{-- Gráfico semanal --}}
            <div class="p-6">
                <x-weekly-worked-hours
                    :attendance-week="$asistenciaSemana"
                />
            </div>

        </section>

    </div>

</x-admin-layout>
