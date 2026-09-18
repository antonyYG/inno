<x-admin-layout>

    <div x-data="{ open: false }">

        <x-header>
            Listado de Areas

            <x-slot name="actions">
                <x-button @click.stop="open = true">
                    Nueva Area
                </x-button>
            </x-slot>
        </x-header>

        <x-table
         :columns="[
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Nombre', 'sortable' => true],
            ['key' => 'created_at', 'label' => 'Creado', 'sortable' => true],
         ]"
         :rows="$areas"
         :search-fields="[
        ['key' => 'name', 'label' => 'Nombre'],
        ]"
        >

        @foreach ($areas as $area)
            <tr>
                <td class="px-4 py-3">{{ $area->id }}</td>
                <td class="px-4 py-3">{{ $area->name }}</td>
                <td class="px-4 py-3">{{ $area->created_at->format('d/m/Y') }}</td>
            </tr>
        @endforeach

        </x-table>


        {{-- Modal --}}
        <x-modal x-show="open" x-transition x-cloak title="Crear Nueva Area">

            <form x-ref="form" action="{{ route('admin.areas.store') }}" method="POST">
                @csrf

                <x-input name="name" value="{{ old('name') }}" />

                <x-input-error for="name" />

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
