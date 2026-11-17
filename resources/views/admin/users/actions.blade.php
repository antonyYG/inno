@php
    $esPracticante = $user->hasRole('practicante');

    $horasRequeridas = (int) ($user->horas_requeridas ?: 360);

    $segundosAcumulados = (int) ($user->segundos_acumulados ?? 0);

    $cumplioHoras = $segundosAcumulados >= ($horasRequeridas * 3600);
@endphp

<div class="flex items-center gap-4">

    {{-- Editar --}}
    <a href="{{ route('admin.users.edit', $user) }}"
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-600 transition-colors hover:bg-indigo-50"
        title="Editar"
        aria-label="Editar usuario">

        <i class="fa-regular fa-pen-to-square"></i>
    </a>


    {{-- Certificado: solamente si completó sus horas --}}
    @if ($esPracticante && $cumplioHoras)

        <a href="{{ route('admin.certificate.generate', $user) }}"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-green-600 transition-colors hover:bg-green-50"
            title="Generar certificado"
            aria-label="Generar certificado">

            <i class="fa-solid fa-file-circle-check"></i>
        </a>

    @elseif ($esPracticante)

        <span
            class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg text-slate-300"
            title="No cumple las {{ $horasRequeridas }} horas requeridas">

            <i class="fa-regular fa-file"></i>
        </span>

    @endif


    {{-- Activar / desactivar --}}
    <label class="inline-flex cursor-pointer items-center"
        title="Activar / desactivar">

        <input
            type="checkbox"
            class="peer sr-only js-toggle-status"
            role="switch"
            data-id="{{ $user->id }}"
            data-url="{{ route('admin.users.toggle-status', $user) }}"
            aria-label="Activar o desactivar usuario {{ $user->nombre }}"
            @checked($user->estado)
            onchange="toggleStatus(this)"
        />

        <div class="relative h-6 w-11 rounded-full bg-black transition-colors
            after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5
            after:rounded-full after:bg-white after:transition-transform
            peer-checked:bg-green-500 peer-checked:after:translate-x-5
            peer-focus-visible:ring-2 peer-focus-visible:ring-green-500 peer-focus-visible:ring-offset-2">
        </div>

    </label>

</div>
