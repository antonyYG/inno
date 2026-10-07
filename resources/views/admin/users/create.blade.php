<x-admin-layout>

    <x-header>
        Crear Usuario
    </x-header>

    <div class="bg-white shadow-xl rounded-xl m-2 sm:m-4 p-3 sm:p-4">

        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-6">
                    <label for="name" class="block mb-1 text-sm font-medium text-heading">
                        Nombre
                    </label>
                    <x-input id="name" name="name" placeholder="Nombre" />
                    <x-input-error for="name" />
                </div>

                <div class="mb-6" x-data="{ open: '{{ old('rol') }}' }">
                    <label for="rol" class="block mb-1 text-sm font-medium text-heading">
                        Rol
                    </label>

                    <x-select name="rol" x-model="open">
                        <option value="" disabled {{ old('rol') ? '' : 'selected' }}>
                            Selecciona un rol
                        </option>
                        <option value="admin" {{ old('rol') == 'admin' ? 'selected' : '' }}>
                            Admin
                        </option>
                        <option value="practicing" {{ old('rol') == 'practicing' ? 'selected' : '' }}>
                            Practicante
                        </option>
                    </x-select>
                    <x-input-error for="rol" />

                    <div class="mb-6 mt-4" x-show="open=='practicing'" x-cloak>
                        <label for="discord_id" class="block mb-1 text-sm font-medium text-heading">
                            Discord_id
                        </label>
                        <x-input id="discord_id" name="discord_id" placeholder="DiscordID" />
                        <x-input-error for="discord_id" />
                    </div>

                    <div class="mb-6" x-show="open=='practicing'" x-cloak>
                        <label for="area" class="block mb-1 text-sm font-medium text-heading">
                            Area
                        </label>
                        <x-select name="area">
                            <option value="" disabled {{ old('area') ? '' : 'selected' }}>
                                Selecciona un área
                            </option>
                            @foreach ($areas as $area)
                            <option value="{{ $area->id }}" {{ old('area') == $area->id ? 'selected' : '' }}>
                                {{ $area->name }}
                            </option>
                            @endforeach
                        </x-select>
                        <x-input-error for="area" />
                    </div>
                </div>

                <div class="mb-6">
                    <label for="email" class="block mb-1 text-sm font-medium text-heading">
                        Email
                    </label>
                    <x-input type="email" name="email" placeholder="Email" />
                    <x-input-error for="email" />
                </div>

                <div class="mb-6">
                    <label for="password" class="block mb-1 text-sm font-medium text-heading">
                        Contraseña
                    </label>
                    <x-input type="password" name="password" placeholder="Contraseña" />
                    <x-input-error for="password" />
                </div>

            </div>

            <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                <x-button type="submit">
                    Guardar
                </x-button>

                <a href="{{ route('admin.users.index') }}">
                    <x-button-danger>
                        Cancelar
                    </x-button-danger>
                </a>
            </div>

        </form>

    </div>
</x-admin-layout>
