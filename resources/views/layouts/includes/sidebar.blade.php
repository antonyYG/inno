@php
    $links = [
        [
            "name" => "Dashboard",
            "icon" => "fa-solid fa-gauge",
            "route" => "admin.dashboard",
            'isRoute' => request()->routeIs('admin.dashboard'),
            'permiso' => 'read.dashboard'
        ],
        [
            "name" => "Usuarios",
            "icon" => "fa-solid fa-user-group",
            "route" => "admin.users.index",
            'isRoute' => request()->routeIs('admin.users.*'),
            'permiso' => 'read.users',
        ],
        [
            "name" => "Areas",
            "icon" => "fa-solid fa-layer-group",
            "route" => "admin.areas.index",
            'isRoute' => request()->routeIs('admin.areas.*'),
            'permiso' => 'read.areas'
        ],
        [
            "name" => "Calendario",
            "icon" => "fa-solid fa-calendar",
            "route" => "admin.calendar.index",
            'isRoute' => request()->routeIs('admin.calendar.index'),
            'permiso' => 'read.calendar',
        ],
        [
            "name" => "Marcar Asistencia",
            "icon" => "fa-solid fa-file-circle-check",
            "route" => "admin.tick.index",
            'isRoute' => request()->routeIs('admin.tick.index'),
            'permiso' => 'mark.attendance'
        ],
    ]
@endphp

<aside id="top-bar-sidebar" class="fixed top-0 left-0 z-40 w-64 h-full pt-16 transition-transform -translate-x-full sm:translate-x-0" aria-label="Menú lateral">
   <div class="h-full px-3 py-4 overflow-y-auto bg-[#020830] border-e border-[#0a1547]">
      <ul class="space-y-1 font-medium">
        @foreach ($links as $link)

            @can($link['permiso'])
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
            @endcan

         @endforeach
      </ul>
   </div>
</aside>
