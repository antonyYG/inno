<x-admin-layout>
    @push('js')
        @vite(['resources/css/app.css', 'resources/js/tableArea.js'])
    @endpush
    <div x-data="{ open: {{ $errors->has('nombre') ? 'true' : 'false' }} }">

        <x-header>
            Listado de Areas

            <x-slot name="actions">
                <x-button @click="open = true">
                    Nueva Area
                </x-button>
            </x-slot>
        </x-header>

       <table id="areas-table" data-url="{{ route('admin.areas.data') }}" class="w-full">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Nombre</th>
                    <th>Fecha Creacion</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>


        {{-- Modal --}}
        <x-modal x-show="open" x-transition x-cloak title="Crear Nueva Area">

            <form x-ref="form" action="{{ route('admin.areas.store') }}" method="POST">
                @csrf

                <x-input name="nombre" value="{{ old('nombre') }}" />

                <x-input-error for="nombre" />

                <div class="mt-6 flex justify-end gap-2">

                    <x-button type="submit">
                        Crear
                    </x-button>

                    <x-button-danger
                        type="button"
                        @click="open = false; $refs.form.reset()"
                        class="bg-gray-500 hover:bg-gray-600"
                    >
                        Cancelar
                    </x-button-danger>

                </div>
            </form>

        </x-modal>

    </div>

</x-admin-layout>
