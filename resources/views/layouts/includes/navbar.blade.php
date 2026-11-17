<nav class="fixed top-0 z-50 w-full border-b border-[#0a1547] bg-[#020830]">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start rtl:justify-end">
                <button data-drawer-target="top-bar-sidebar" data-drawer-toggle="top-bar-sidebar"
                    aria-controls="top-bar-sidebar" type="button"
                    class="sm:hidden text-slate-200 bg-transparent box-border border border-transparent hover:bg-white/10 focus:ring-4 focus:ring-[#f2c94c]/30 font-medium leading-5 rounded-lg text-sm p-2 focus:outline-none">
                    <span class="sr-only">Abrir menú lateral</span>
                    <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                            d="M5 7h14M5 12h14M5 17h10" />
                    </svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center ms-2 md:me-24 gap-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        class="h-7 w-7 shrink-0 text-[#f2c94c]">
                        <path d="M12 2 4 5v6c0 5.25 3.4 9.74 8 11 4.6-1.26 8-5.75 8-11V5l-8-3Z" stroke="currentColor"
                            stroke-width="1.5" />
                        <path d="M8 12h8M8 9h8M8 15h5" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" />
                    </svg>
                    <span class="self-center text-lg font-semibold leading-tight whitespace-nowrap text-white">
                        CRUD <span class="text-[#f2c94c]">Agency</span>
                    </span>
                </a>
            </div>
            <div class="flex items-center">
                @if (auth()->user()->hasRole('admin'))
                <div id="admin-notificaciones" class="relative mr-4" data-url="{{ route('admin.notificaciones.data') }}"
                    data-read-url="{{ route('admin.notificaciones.read', ':id') }}">
                    <button type="button" id="btn-notificaciones"
                        class="relative p-2 text-white hover:bg-white/10 rounded-lg" aria-label="Notificaciones">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 00-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 01-6 0m6 0H9" />
                        </svg>

                        <span id="contador-notificaciones"
                            class="hidden absolute -top-1 -right-1 min-w-5 h-5 px-1
                   items-center justify-center rounded-full bg-red-500
                   text-white text-xs font-bold">0</span>
                    </button>

                    <div id="panel-notificaciones"
                        class="hidden absolute right-0 mt-3 w-80 sm:w-96 bg-white
               rounded-xl shadow-xl border border-gray-200 z-50">
                        <div class="flex justify-between items-center p-4 border-b">
                            <h3 class="font-semibold text-gray-800">
                                Notificaciones
                            </h3>
                            <span class="text-xs text-gray-500">Solicitudes recibidas</span>
                        </div>

                        <div id="lista-notificaciones" class="max-h-96 overflow-y-auto">
                            <p class="p-5 text-center text-sm text-gray-500">
                                Cargando notificaciones...
                            </p>
                        </div>
                    </div>
                </div>
                @endif
                <div class="flex items-center ms-3">
                    <div>
                        <button type="button"
                            class="flex text-sm bg-[#0a1547] rounded-full ring-2 ring-transparent focus:outline-none focus:ring-[#f2c94c]"
                            aria-haspopup="true" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                            <span class="sr-only">Abrir menú de usuario</span>
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-[#f2c94c] text-sm font-semibold text-[#020830]"
                                aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->nombre, 0, 1)) }}</span>
                        </button>
                    </div>
                    <div class="z-50 hidden bg-white border border-gray-100 rounded-xl shadow-lg w-48"
                        id="dropdown-user">
                        <div class="px-4 py-3 border-b border-gray-100" role="none">
                            <p class="text-sm font-medium text-gray-900" role="none">
                                {{ auth()->user()->nombre . ' ' . auth()->user()->apellidos }}
                            </p>
                            <p class="text-sm text-gray-500 truncate" role="none">
                                {{ auth()->user()->email }}
                            </p>
                        </div>
                        <ul class="p-2 text-sm font-medium text-gray-600" role="none">
                            <li>
                                <a href="{{ route('admin.dashboard') }}"
                                    class="inline-flex items-center w-full p-2 hover:bg-[#f2c94c]/10 hover:text-[#020830] rounded-lg transition-colors"
                                    role="menuitem">Dashboard</a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center w-full p-2 hover:bg-[#f2c94c]/10 hover:text-[#020830] rounded-lg transition-colors"
                                        role="menuitem">Cerrar sesión</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
