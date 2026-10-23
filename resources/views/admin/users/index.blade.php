<x-admin-layout>
    @push('js')
        @vite(['resources/css/app.css', 'resources/js/tableUser.js'])
    @endpush
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
<select id="filter-rol">
    <option value="">Todos los roles</option>
    <option value="admin">Administrador</option>
    <option value="practicing">Practicante</option>
</select>
    <table id="users-table" data-url="{{ route('admin.users.data') }}" class="w-full">
        <thead>
            <tr>
                <th>Id</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
    </table>
    


</x-admin-layout>
