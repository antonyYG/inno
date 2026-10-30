<x-admin-layout>

    <x-header>
        Crear Usuario
    </x-header>

    <div class="w-full rounded-xl border border-slate-200 bg-white shadow-sm">

        <form method="POST" action="{{ route('admin.users.store') }}" x-data="{ open: @js(old('rol', '')) }">
            @csrf

            <div class="space-y-8 p-5 sm:p-6">

                <p class="text-sm text-slate-500">
                    Los campos marcados con <span class="font-semibold text-red-600">*</span> son obligatorios.
                </p>

                {{-- Datos personales --}}
                <fieldset>
                    <legend class="mb-4 w-full border-b border-slate-100 pb-2 text-base font-semibold text-slate-900">
                        Datos personales
                    </legend>

                    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-3">
                        <div>
                            <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">
                                Nombre <span class="text-red-600">*</span>
                            </label>
                            <x-input id="nombre" name="nombre" value="{{ old('nombre') }}" placeholder="Ej. María Pérez" />
                            <x-input-error for="nombre" />
                        </div>

                        <div>
                            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">
                                Correo electrónico <span class="text-red-600">*</span>
                            </label>
                            <x-input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="correo@empresa.com" />
                            <x-input-error for="email" />
                        </div>
                        <div>
                            <label for="apellidos" class="mb-1 block text-sm font-medium text-slate-700">
                                Apellidos <span class="text-red-600">*</span>
                            </label>
                            <x-input id="apellidos" name="apellidos" value="{{ old('apellidos') }}" placeholder="Perez Gomez" />
                            <x-input-error for="apellidos" />
                        </div>
                    </div>
                </fieldset>

                {{-- Acceso al sistema --}}
                <fieldset>
                    <legend class="mb-4 w-full border-b border-slate-100 pb-2 text-base font-semibold text-slate-900">
                        Acceso al sistema
                    </legend>

                    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
                        <div>
                            <label for="rol" class="mb-1 block text-sm font-medium text-slate-700">
                                Rol <span class="text-red-600">*</span>
                            </label>
                            <x-select id="rol" name="rol" x-model="open">
                                <option value="" disabled {{ old('rol') ? '' : 'selected' }}>
                                    Selecciona un rol
                                </option>
                                <option value="admin" {{ old('rol') == 'admin' ? 'selected' : '' }}>
                                    Admin
                                </option>
                                <option value="practicante" {{ old('rol') == 'practicante' ? 'selected' : '' }}>
                                    Practicante
                                </option>
                            </x-select>
                            <x-input-error for="rol" />
                        </div>

                        <div>
                            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
                                Contraseña <span class="text-red-600">*</span>
                            </label>
                            <x-input id="password" type="password" name="password" placeholder="Mínimo 8 caracteres" />
                            <x-input-error for="password" />
                        </div>
                    </div>
                </fieldset>

                {{-- Datos de practicante (solo si el rol es Practicante) --}}
                <fieldset x-show="open == 'practicing'" x-cloak>
                    <legend class="mb-4 w-full border-b border-slate-100 pb-2 text-base font-semibold text-slate-900">
                        Datos del practicante
                    </legend>

                    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
                        <div>
                            <label for="discord_id" class="mb-1 block text-sm font-medium text-slate-700">
                                Discord ID <span class="text-red-600">*</span>
                            </label>
                            <x-input id="discord_id" name="discord_id" value="{{ old('discord_id') }}" placeholder="Ej. 123456789012345678" />
                            <x-input-error for="discord_id" />
                        </div>

                        <div>
                            <label for="area_id" class="mb-1 block text-sm font-medium text-slate-700">
                                Área <span class="text-red-600">*</span>
                            </label>
                            <x-select-search
                                id="area_id"
                                name="area_id"
                                :options="$areas->pluck('nombre', 'id')"
                                :value="old('area_id')"
                                placeholder="Selecciona un área"
                                search-placeholder="Buscar área..."
                            />
                            <x-input-error for="area_id" />
                        </div>
                    </div>
                </fieldset>
            </div>

            {{-- Acciones --}}
            <div class="flex flex-col-reverse gap-3 rounded-b-xl border-t border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#020830]/30">
                    Cancelar
                </a>
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>

        </form>

    </div>
</x-admin-layout>
