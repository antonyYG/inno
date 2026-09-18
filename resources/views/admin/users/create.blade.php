<x-admin-layout>

    <x-header>
        Crear Usuario
    </x-header>

    <div class="bg-white shadow-xl rounded-xl m-2 sm:m-4 p-3 sm:p-4">

        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-6">
                    <label for="nombre" class="block mb-1 text-sm font-medium text-heading">
                        Nombre
                    </label>
                    <x-input id="name" name="name" placeholder="Nombre" value="{{ old('name') }}" />
                    <x-input-error for="name" />
                </div>

                <div class="mb-6" x-data={open:''}>
                    <label for="Rol" class="block mb-1 text-sm font-medium text-heading">
                        Rol
                    </label>

                        <x-select name="rol" x-model="open">
                            <option name="admin" value="admin">
                                Admin
                            </option>
                            <option name="practicing" value="practicing">
                                Practicante
                            </option>
                        </x-select>
                        <x-input-error for="rol" />

                     <div class="mb-6" x-show="open=='practicing'" x-cloak>
                        <label for="discor_id" class="block mb-1 text-sm font-medium text-heading">
                            Discord_id
                        </label>
                        <x-input id="name" name="discord_id" placeholder="DiscordID" value="{{ old('discord_id') }}" />
                        <x-input-error for="discord_id" />
                    </div>
                </div>


                <div class="mb-6">
                    <label for="email" class="block mb-1 text-sm font-medium text-heading">
                        Email
                    </label>
                    <x-input type="email" name="email" placeholder="Email" value="{{ old('email') }}" />
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
