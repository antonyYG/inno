{{-- resources/views/users/actions.blade.php --}}
<div x-data="{ open: false }">
    <button @click="open=true" class="text-indigo-600 hover:underline">Editar</button>

    <x-modal x-show="open" x-transition x-cloak title="Crear Nueva Area">

        <form x-ref="form" action="{{ route('admin.areas.update',$area) }}" method="POST">
            @csrf
            @method('PUT')

            <x-input name="nombre" value="{{ old('nombre',$area->nombre) }}" />

            <x-input-error for="nombre" />

            <div class="mt-6 flex justify-end gap-2">

                <x-button type="submit">
                    Crear
                </x-button>

                <x-button-danger type="button" @click="open = false; $refs.form.reset()"
                    class="bg-gray-500 hover:bg-gray-600">
                    Cancelar
                </x-button-danger>

            </div>
        </form>

    </x-modal>
</div>


{{-- Eliminacion --}}

<div>
    <form
        action="{{ route('admin.areas.destroy', $area) }}"
        method="POST"
        x-data
        @submit.prevent="
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'No podrás revertir esta acción.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $el.submit();
                }
            })
        "
    >
        @csrf
        @method('DELETE')

        <button
            type="submit"
            class="text-red-600 hover:underline"
        >
            Eliminar
        </button>
    </form>
</div>
