<x-admin-layout>

    <x-header>
        Listado de Usuarios

        <x-slot name="actions">
            <a href="{{ route('admin.users.create') }}">
                <x-button>
                    Nuevo Usuario
                </x-button>
            </a>

        </x-slot>

    </x-header>

    <x-table
        :columns="[
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'name', 'label' => 'Nombre', 'sortable' => true],
            ['key' => 'email', 'label' => 'Email', 'sortable' => true],
            ['key' => 'created_at', 'label' => 'Creado', 'sortable' => true],
        ]"
        :rows="$users"
        :search-fields="[
        ['key' => 'name', 'label' => 'Nombre'],
        ['key' => 'description', 'label' => 'Descripción'],
    ]"
    >
        @foreach ($users as $user)
            <tr>
                <td class="px-4 py-3">{{ $user->id }}</td>
                <td class="px-4 py-3">{{ $user->name }}</td>
                <td class="px-4 py-3">{{ $user->email }}</td>
                <td class="px-4 py-3">{{ $user->created_at->format('d/m/Y') }}</td>
            </tr>
        @endforeach
    </x-table>


</x-admin-layout>
