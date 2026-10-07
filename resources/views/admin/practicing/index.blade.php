<x-admin-layout>

    <x-header>

        Listado de practicantes

    </x-header>

    <x-table :columns="[
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'discord_id', 'label' => 'DiscordId', 'sortable' => true],
        ['key' => 'user.name', 'label' => 'Usuario', 'sortable' => true],
        ['key' => 'area.name', 'label' => 'Area', 'sortable' => true],
        ['key' => 'created_at', 'label' => 'Creado', 'sortable' => true],
    ]" :rows="$practicings"
    :search-fields="[
        ['key' => 'user.name', 'label' => 'Nombre']
    ]"
    :selectFilters="[
        [
            'key' => 'status',
            'label' => 'Estado',
            'placeholder' => 'Todos los estados',
            'options' => [
                '1' => 'Activo',
                '0' => 'Inactivo',
            ],
        ],
    ]"

    >

    @foreach ($practicings as $practicing)
        <tr>
            <td class="px-4 py-3">{{ $practicing->id }}</td>
            <td class="px-4 py-3">{{ $practicing->discord_id }}</td>
            <td class="px-4 py-3">{{ $practicing->user->name}}</td>
            <td class="px-4 py-3">{{ $practicing->area->name }}</td>
            <td class="px-4 py-3">{{ $practicing->created_at->format('d/m/Y') }}</td>
        </tr>
    @endforeach

    </x-table>

</x-admin-layout>
