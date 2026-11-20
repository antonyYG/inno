@php
    $groups = [
        [
            'label' => 'GENERAL',
            'links' => [
                [
                    "name" => "Dashboard",
                    "icon" => "fa-solid fa-gauge",
                    "route" => "admin.dashboard",
                    'isRoute' => request()->routeIs('admin.dashboard'),
                    'permiso' => 'read.dashboard'
                ],
            ],
        ],
        [
            'label' => 'GESTIÓN',
            'links' => [
                [
                    "name" => "Usuarios",
                    "icon" => "fa-solid fa-user-group",
                    "route" => "admin.users.index",
                    'isRoute' => request()->routeIs('admin.users.*'),
                    'permiso' => 'read.users',
                ],
                [
                    "name" => "Áreas",
                    "icon" => "fa-solid fa-layer-group",
                    "route" => "admin.areas.index",
                    'isRoute' => request()->routeIs('admin.areas.*'),
                    'permiso' => 'read.areas'
                ],
            ],
        ],
        [
            'label' => 'ASISTENCIA',
            'links' => [
                [
                    "name" => "Calendario",
                    "icon" => "fa-solid fa-calendar-days",
                    "route" => "admin.calendar.index",
                    'isRoute' => request()->routeIs('admin.calendar.*'),
                    'permiso' => 'read.calendar',
                ],
                [
                    "name" => "Marcar Asistencia",
                    "icon" => "fa-solid fa-calendar-check",
                    "route" => "admin.tick.index",
                    'isRoute' => request()->routeIs('admin.tick.*'),
                    'permiso' => 'mark.attendance'
                ],
            ],
        ],
        [
            'label' => 'ANÁLISIS Y CONTROL',
            'links' => [
                [
                    "name" => "Reportes",
                    "icon" => "fa-solid fa-chart-column",
                    "route" => "admin.reportes.index",
                    'isRoute' => request()->routeIs('admin.reportes.*'),
                    'permiso' => 'read.users'
                ],
                [
                    "name" => "Auditorías",
                    "icon" => "fa-solid fa-clipboard-list",
                    "route" => "admin.auditorias.index",
                    'isRoute' => request()->routeIs('admin.auditorias.*'),
                    'permiso' => 'read.users'
                ],
            ],
        ],
    ];
@endphp

<aside id="top-bar-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-full pt-16 transition-transform -translate-x-full sm:translate-x-0"
    aria-label="Menú lateral">

    <div class="h-full px-3 py-4 overflow-y-auto bg-[#020830] border-e border-[#0a1547]">

        <ul class="space-y-4 font-medium">

            @foreach ($groups as $group)

                @php
                    $visibleLinks = collect($group['links'])
                        ->filter(fn($link) => auth()->user()->can($link['permiso']));
                @endphp

                @if ($visibleLinks->isNotEmpty())

                    <li>
                        {{-- Etiqueta del grupo --}}
                        <p class="px-3 mb-2 text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                            {{ $group['label'] }}
                        </p>

                        <ul class="space-y-1">

                            @foreach ($visibleLinks as $link)

                                <li>
                                    <a href="{{ route($link['route']) }}"
                                        @if ($link['isRoute']) aria-current="page" @endif
                                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#f2c94c]
                                        {{ $link['isRoute']
                                            ? 'bg-[#f2c94c] text-[#020830] font-semibold'
                                            : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">

                                        <i class="{{ $link['icon'] }} w-5 text-center" aria-hidden="true"></i>

                                        <span class="text-sm">{{ $link['name'] }}</span>

                                    </a>
                                </li>

                            @endforeach

                        </ul>
                    </li>

                @endif

            @endforeach

        </ul>
    </div>
</aside>
