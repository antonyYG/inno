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
                <div id="admin-notificaciones" class="relative mr-2 sm:mr-4" data-url="{{ route('admin.notificaciones.data') }}"
                    data-read-url="{{ route('admin.notificaciones.read', ':id') }}">
                    <button type="button" id="btn-notificaciones"
                        class="relative rounded-lg p-2 text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[#f2c94c]/60"
                        aria-label="Notificaciones" aria-haspopup="dialog" aria-expanded="false"
                        aria-controls="panel-notificaciones">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 00-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 01-6 0m6 0H9" />
                        </svg>

                        <span id="contador-notificaciones"
                            class="absolute -right-1 -top-1 hidden h-5 min-w-5 items-center justify-center rounded-full bg-[#f2c94c] px-1 text-xs font-bold text-[#020830] ring-2 ring-[#020830]">0</span>
                    </button>

                    <div id="panel-notificaciones" role="dialog" aria-label="Notificaciones"
                        class="absolute right-0 z-50 mt-3 hidden w-[calc(100vw-1.5rem)] max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:w-96">
                        <div class="border-b border-slate-100 px-4 pb-3 pt-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">Notificaciones</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">Solicitudes y novedades recientes</p>
                                </div>

                                <span class="rounded-full bg-[#f2c94c]/15 px-2.5 py-1 text-[11px] font-semibold text-[#8a6a00]">
                                    PractiControl
                                </span>
                            </div>

                            <div class="mt-3 flex gap-1 rounded-xl bg-slate-100 p-1" role="tablist"
                                aria-label="Estado de notificaciones">
                                <button type="button" data-tab="pendientes" role="tab" aria-selected="true"
                                    class="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-[#020830] shadow-sm transition">
                                    Pendientes
                                    <span id="contador-pendientes"
                                        class="rounded-full bg-[#f2c94c] px-1.5 py-0.5 text-[10px] font-bold text-[#020830]">0</span>
                                </button>

                                <button type="button" data-tab="historial" role="tab" aria-selected="false"
                                    class="flex flex-1 items-center justify-center rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-800">
                                    Historial
                                </button>
                            </div>
                        </div>

                        <div id="lista-notificaciones" data-estado="pendientes" role="tabpanel"
                            class="max-h-[min(60vh,22rem)] overflow-y-auto overscroll-contain">
                            <div class="px-5 py-8 text-center text-sm text-slate-500">
                                Cargando notificaciones...
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 border-t border-slate-100 bg-slate-50/70 px-4 py-2">
                            <p class="text-[11px] text-slate-500">
                                Al abrir una notificación se marca como leída.
                            </p>
                            <p class="shrink-0 text-[11px] font-medium text-slate-400">
                                Se conserva en el historial
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
