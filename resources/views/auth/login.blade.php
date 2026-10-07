<x-auth-layout title="Iniciar sesión - CRUD Agency">

    {{-- Marca + encabezado --}}
    <div class="mb-8">
        <div class="mb-6 flex h-11 w-11 items-center justify-center rounded-xl bg-[#020830]" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-6 w-6 text-[#f2c94c]">
                <rect x="3.5" y="5" width="17" height="15" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                <path d="M8 3v4M16 3v4M3.5 10h17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                <path d="m9.5 15 1.8 1.8 3.4-3.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h1 class="text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">
            Bienvenido de nuevo
        </h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            Ingresa tus credenciales para acceder al sistema de asistencias.
        </p>
    </div>

    <form action="{{ route('login.submit') }}" method="POST"
          class="flex w-full flex-col gap-5"
          x-data="{ loading: false }"
          @submit="loading = true"
          @pageshow.window="loading = false">
        @csrf

        {{-- Email --}}
        <div class="flex w-full flex-col">
            <label for="email-alternative" class="mb-1.5 text-sm font-medium text-slate-700">
                Correo electrónico
            </label>
            <div class="group relative">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true"
                     class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors group-focus-within:text-[#020830] motion-reduce:transition-none">
                    <path d="M3 6.5 12 13l9-6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="3" y="5" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.5"/>
                </svg>
                <input name="email" type="email" id="email-alternative"
                       value="{{ old('email') }}"
                       autocomplete="username"
                       inputmode="email"
                       placeholder="correo@ejemplo.com"
                       required autofocus
                       @if ($errors->has('email')) aria-invalid="true" @endif
                       aria-describedby="email-error"
                       @class([
                           'block h-12 w-full rounded-lg border bg-white pl-11 pr-3.5 text-base text-slate-900 shadow-sm outline-none transition-colors placeholder:text-slate-400 sm:text-sm motion-reduce:transition-none',
                           'border-slate-400 hover:border-slate-500 focus:border-[#020830] focus:ring-4 focus:ring-[#f2c94c]/40' => ! $errors->has('email'),
                           'border-red-600 bg-red-50/40 focus:border-red-600 focus:ring-4 focus:ring-red-600/20' => $errors->has('email'),
                       ]) />
            </div>
            <div id="email-error" aria-live="polite">
                <x-input-error for="email" class="mt-1.5" />
            </div>
        </div>

        {{-- Password --}}
        <div class="flex w-full flex-col">
            <label for="password-alternative" class="mb-1.5 text-sm font-medium text-slate-700">
                Contraseña
            </label>
            <div class="group relative" x-data="{ show: false }">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true"
                     class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400 transition-colors group-focus-within:text-[#020830] motion-reduce:transition-none">
                    <rect x="4" y="10" width="16" height="10" rx="2.5" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M8 10V7a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <input type="password" id="password-alternative" name="password"
                       :type="show ? 'text' : 'password'"
                       autocomplete="current-password"
                       placeholder="••••••••"
                       required
                       @if ($errors->has('password')) aria-invalid="true" @endif
                       aria-describedby="password-error"
                       @class([
                           'block h-12 w-full rounded-lg border bg-white pl-11 pr-12 text-base text-slate-900 shadow-sm outline-none transition-colors placeholder:text-slate-400 sm:text-sm motion-reduce:transition-none',
                           'border-slate-400 hover:border-slate-500 focus:border-[#020830] focus:ring-4 focus:ring-[#f2c94c]/40' => ! $errors->has('password'),
                           'border-red-600 bg-red-50/40 focus:border-red-600 focus:ring-4 focus:ring-red-600/20' => $errors->has('password'),
                       ]) />
                <button type="button"
                        @click="show = ! show"
                        :aria-pressed="show.toString()"
                        :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        aria-label="Mostrar contraseña"
                        aria-pressed="false"
                        class="absolute right-1.5 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#020830] motion-reduce:transition-none">
                    {{-- Ojo (contraseña oculta) --}}
                    <svg x-show="! show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-5 w-5">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                    {{-- Ojo tachado (contraseña visible) --}}
                    <svg x-show="show" style="display: none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-5 w-5">
                        <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M10.6 6.1A9.8 9.8 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-3.2 3.9M6.5 7.6C3.6 9.4 2 12 2 12s3.5 7 10 7c1.6 0 3-.4 4.2-1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9.9 10a3 3 0 0 0 4.1 4.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <div id="password-error" aria-live="polite">
                <x-input-error for="password" class="mt-1.5" />
            </div>
        </div>

        {{-- Botón --}}
        <button type="submit"
                :disabled="loading"
                :aria-busy="loading.toString()"
                class="mt-1 inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#020830] px-4 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#0a1547] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#f2c94c]/60 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 motion-safe:active:scale-[0.99] motion-reduce:transition-none">
            <svg x-show="loading" style="display: none" class="h-4 w-4 animate-spin motion-reduce:animate-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
            <span x-show="! loading">Iniciar sesión</span>
            <span x-show="loading" style="display: none">Ingresando…</span>
        </button>
    </form>

    {{-- Ayuda --}}
    <p class="mt-8 border-t border-slate-200 pt-6 text-center text-sm text-slate-600">
        ¿Necesitas ayuda?
        <a href="#"
           class="rounded font-medium text-[#020830] underline decoration-[#f2c94c] decoration-2 underline-offset-4 transition-colors hover:decoration-[#020830] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#020830] focus-visible:ring-offset-2 motion-reduce:transition-none">
            Contacta a soporte
        </a>
    </p>

</x-auth-layout>
