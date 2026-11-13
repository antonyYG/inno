{{-- Acciones del listado de justificaciones: revisar archivo y aceptar/rechazar --}}
@php
    $validarUrl = $justificacion->tipo === 'justificacion'
        ? route('admin.justificaciones.validar', $justificacion->asistencia_id)
        : route('admin.asistencias.validar', $justificacion->asistencia_id);

    $rechazarUrl = $justificacion->tipo === 'justificacion'
        ? route('admin.justificaciones.rechazar', $justificacion->asistencia_id)
        : route('admin.asistencias.rechazar', $justificacion->asistencia_id);
@endphp

<div
    x-data="{
        revisando: false,

        async decidir(aceptar) {
            let observacion = null;

            if (!aceptar) {
                const motivo = await Swal.fire({
                    title: 'Rechazar',
                    input: 'textarea',
                    inputLabel: 'Observación',
                    inputPlaceholder: 'Escribe el motivo del rechazo...',
                    showCancelButton: true,
                    confirmButtonText: 'Rechazar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc2626',
                });

                if (!motivo.isConfirmed) return;

                observacion = motivo.value || '';
            } else {
                const confirmar = await Swal.fire({
                    title: '¿Aceptar?',
                    text: 'Se registrará la decisión sobre lo presentado.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Aceptar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#111827',
                });

                if (!confirmar.isConfirmed) return;
            }

            this.revisando = true;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                const response = await fetch(
                    aceptar ? '{{ $validarUrl }}' : '{{ $rechazarUrl }}',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ observacion }),
                    }
                );

                const result = await response.json();

                if (!response.ok) {
                    const validationMessage = result.errors
                        ? Object.values(result.errors).flat().join('\n')
                        : null;

                    throw new Error(validationMessage || result.message || 'No se pudo completar la operación.');
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Operación realizada',
                    text: result.message,
                    confirmButtonColor: '#111827',
                });

                window.location.reload();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                });
            } finally {
                this.revisando = false;
            }
        },
    }"
    class="flex items-center gap-3"
>
    {{-- Ver archivo --}}
    <a
        href="{{ Storage::url($justificacion->archivo_path) }}"
        target="_blank"
        rel="noopener noreferrer"
        class="text-indigo-600 hover:text-indigo-800"
        title="Ver archivo"
    >
        <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
    </a>

    @if ($justificacion->estado === 'pendiente')
        {{-- Aceptar --}}
        <button
            type="button"
            @click="decidir(true)"
            :disabled="revisando"
            class="text-green-600 hover:text-green-800 disabled:opacity-50"
            title="Aceptar"
        >
            <i class="fa-solid fa-check" aria-hidden="true"></i>
        </button>

        {{-- Rechazar --}}
        <button
            type="button"
            @click="decidir(false)"
            :disabled="revisando"
            class="text-red-600 hover:text-red-800 disabled:opacity-50"
            title="Rechazar"
        >
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    @endif

    {{-- Estado actual --}}
    <span
        class="text-xs font-semibold rounded-full px-2 py-0.5 capitalize
            {{ $justificacion->estado === 'aprobada' ? 'bg-green-50 text-green-700' : '' }}
            {{ $justificacion->estado === 'rechazada' ? 'bg-red-50 text-red-700' : '' }}
            {{ $justificacion->estado === 'pendiente' ? 'bg-yellow-50 text-yellow-700' : '' }}"
    >
        {{ $justificacion->estado }}
    </span>
</div>
