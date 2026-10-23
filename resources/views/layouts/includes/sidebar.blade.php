@php
    $links = [
        [
            "name" => "Dashboard",
            "icon" => "fa-solid fa-gauge",
            "route" => "admin.dashboard",
            'isRoute' => request()->routeIs('admin.dashboard')
        ],
        [
            "name" => "Usuarios",
            "icon" => "fa-solid fa-user-group",
            "route" => "admin.users.index",
            'isRoute' => request()->routeIs('admin.users.*')
        ],
        [
            "name" => "Areas",
            "icon" => "fa-solid fa-layer-group",
            "route" => "admin.areas.index",
            'isRoute' => request()->routeIs('admin.areas.*')
        ],
        [
            "name" => "Calendario",
            "icon" => "fa-solid fa-calendar",
            "route" => "admin.calendar.index",
            'isRoute' => request()->routeIs('admin.calendar.index')
        ],
        [
            "name" => "Justificaciones",
            "icon" => "fa-solid fa-calendar",
            "route" => "admin.justifications.index",
            'isRoute' => request()->routeIs('admin.justifications.*')
        ],
    ]
@endphp

<aside id="top-bar-sidebar" class="fixed top-0 left-0 z-40 w-64 h-full pt-16 transition-transform -translate-x-full sm:translate-x-0" aria-label="Sidebar">
   <div class="h-full px-3 py-4 overflow-y-auto bg-[#020830] border-e border-[#0a1547]">
      <ul class="space-y-1.5 font-medium">
        @foreach ($links as $link)
            <li>
                <a href="{{ route($link['route']) }}"
                    class="flex items-center px-3 py-2.5 rounded-lg group transition-colors
                    {{ $link['isRoute']
                        ? 'bg-[#f2c94c] text-[#020830] font-semibold'
                        : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="{{ $link['icon'] }}" ></i>
                <span class="ms-3 text-sm">{{ $link['name'] }}</span>
                </a>
            </li>
         @endforeach
      </ul>
   </div>
</aside>
