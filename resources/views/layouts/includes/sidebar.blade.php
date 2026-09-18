@php
    $links = [
        [
            "name" => "Dashboard",
            "route" => "admin.dashboard",
            'isRoute' => request()->routeIs('admin.dashboard')
        ],
        [
            "name" => "Usuarios",
            "route" => "admin.users.index",
            'isRoute' => request()->routeIs('admin.users.*')
        ],
        [
            "name" => "Areas",
            "route" => "admin.areas.index",
            'isRoute' => request()->routeIs('admin.areas.*')
        ],
        [
            "name" => "Calendario",
            "route" => "admin.calendar.index",
            'isRoute' => request()->routeIs('admin.calendar.index')
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
                <svg class="w-5 h-5 transition duration-75 {{ $link['isRoute'] ? 'text-[#020830]' : 'text-slate-400 group-hover:text-white' }}"
                    aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6.025A7.5 7.5 0 1 0 17.975 14H10V6.025Z"/>
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 3c-.169 0-.334.014-.5.025V11h7.975c.011-.166.025-.331.025-.5A7.5 7.5 0 0 0 13.5 3Z"/>
                </svg>
                <span class="ms-3 text-sm">{{ $link['name'] }}</span>
                </a>
            </li>
         @endforeach
      </ul>
   </div>
</aside>
