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
    <div class="mb-4 flex flex-col gap-1 sm:max-w-xs">
        <label for="filter-rol" class="text-sm font-medium text-slate-700">Filtrar por rol</label>
        <select id="filter-rol" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-[#020830] focus:outline-none focus:ring-2 focus:ring-[#020830]/20">
            <option value="">Todos los roles</option>
            <option value="admin">Administrador</option>
            <option value="practicing">Practicante</option>
        </select>
    </div>
    <table id="users-table" data-url="{{ route('admin.users.data') }}" class="w-full">
        <thead>
            <tr>
                <th>Id</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Apellidos</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
    </table>
@push('scripts')
<script>
    window.toggleStatus = async function (input) {
        const anterior = !input.checked;
        input.disabled = true;

        try {
            const response = await fetch(input.dataset.url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) throw new Error();

            const data = await response.json();
            input.checked = data.estado;

            if (data.swal) {
                Swal.fire({
                    title: data.swal.title,
                    text: data.swal.message,
                    icon: data.swal.icon,
                    confirmButtonColor: '#020830',
                });
            }
        } catch (error) {
            input.checked = anterior;
            Swal.fire('Error', 'No se pudo actualizar el estado.', 'error');
        } finally {
            input.disabled = false;
        }
    };
</script>
@endpush

</x-admin-layout>
