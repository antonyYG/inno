<x-admin-layout>
    @push('js')
        @vite(['resources/css/app.css', 'resources/js/tableJustificaciones.js'])
    @endpush
        <x-header>
            Listado de faltas/Tardanzas

        </x-header>
       <table id="justificaciones-table" data-url="{{ route('admin.justifications.data') }}" class="w-full">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>



</x-admin-layout>
