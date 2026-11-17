<x-admin-layout>

    @push('js')
        @vite(['resources/css/app.css', 'resources/js/tableAuditorias.js'])
    @endpush

    <x-header>
        Auditoría administrativa
    </x-header>

    <div class="bg-white rounded-lg shadow-sm p-4 mt-4">

        <div class="mb-4">
            <h2 class="text-lg font-semibold text-gray-800">
                Historial de actividades
            </h2>

            <p class="text-sm text-gray-500">
                Registro de las acciones realizadas por los administradores.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table
                id="auditorias-table"
                data-url="{{ route('admin.auditorias.data') }}"
                class="w-full"
            >
                <thead>
                    <tr>
                        <th>Administrador</th>
                        <th>Acción</th>
                        <th>Practicante</th>
                        <th>Estado anterior</th>
                        <th>Estado nuevo</th>
                        <th>Fecha y hora</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

</x-admin-layout>