<x-auth-layout title="Iniciar sesión - CRUD Agency">

    <h1 class="text-2xl font-semibold text-gray-900 sm:text-3xl">
        Bienvenido de nuevo
    </h1>
    <p class="mt-2 mb-8 text-sm text-gray-500">
        Ingresa tus credenciales para acceder a tu cuenta.
    </p>

    <form action="{{ route('login.submit') }}" method="POST" class="flex w-full flex-col space-y-5">
        @csrf

        {{-- Email --}}
        <div class="flex w-full flex-col">
            <label for="email-alternative" class="mb-1.5 text-sm font-medium text-gray-700">
                Correo electrónico
            </label>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                    class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400">
                    <path d="M3 6.5 12 13l9-6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="3" y="5" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.5"/>
                </svg>
                <input name="email" type="email" id="email-alternative"
                    value="{{ old('email') }}"
                    class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-11 pr-3.5 text-sm text-gray-900 shadow-sm outline-none transition-all placeholder:text-gray-400 focus:border-[#f2c94c] focus:ring-4 focus:ring-[#f2c94c]/20"
                    placeholder="correo@ejemplo.com" required autofocus />
            </div>
            <x-input-error for="email" class="mt-1.5" />
        </div>

        {{-- Password --}}
        <div class="flex w-full flex-col">
            <label for="password-alternative" class="mb-1.5 text-sm font-medium text-gray-700">
                Contraseña
            </label>
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                    class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400">
                    <rect x="4" y="10" width="16" height="10" rx="2.5" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M8 10V7a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <input type="password" id="password-alternative" name="password"
                    class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-11 pr-11 text-sm text-gray-900 shadow-sm outline-none transition-all placeholder:text-gray-400 focus:border-[#f2c94c] focus:ring-4 focus:ring-[#f2c94c]/20"
                    placeholder="••••••••" required />
                <button type="button" x-data @click="
                        const input = $el.previousElementSibling;
                        input.type = input.type === 'password' ? 'text' : 'password';
                    "
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 transition-colors hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.5" />
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </button>
            </div>
            <x-input-error for="password" class="mt-1.5" />
        </div>
        {{-- Botón --}}
        <button type="submit"
            class="mt-2 w-full rounded-xl bg-[#020830] py-3.5 text-sm font-semibold text-white shadow-lg shadow-[#020830]/20 transition-all hover:bg-[#0a1547] focus:outline-none focus:ring-4 focus:ring-[#f2c94c]/40 active:scale-[0.99]">
            Iniciar sesión
        </button>
    </form>

    <p class="mt-8 text-center text-sm text-gray-500">
        ¿Necesitas ayuda?
        <a href="#" class="font-medium text-[#020830] hover:text-[#f2c94c] hover:underline">Contacta a soporte</a>
    </p>

</x-auth-layout>
