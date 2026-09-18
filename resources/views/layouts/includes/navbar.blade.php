<nav class="fixed top-0 z-50 w-full border-b border-[#0a1547] bg-[#020830]">
  <div class="px-3 py-3 lg:px-5 lg:pl-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center justify-start rtl:justify-end">
        <button data-drawer-target="top-bar-sidebar" data-drawer-toggle="top-bar-sidebar" aria-controls="top-bar-sidebar" type="button" class="sm:hidden text-slate-200 bg-transparent box-border border border-transparent hover:bg-white/10 focus:ring-4 focus:ring-[#f2c94c]/30 font-medium leading-5 rounded-lg text-sm p-2 focus:outline-none">
            <span class="sr-only">Open sidebar</span>
            <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
  <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/>
   </svg>
         </button>
        <a href="{{ route('admin.dashboard') }}" class="flex items-center ms-2 md:me-24 gap-2.5">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-7 w-7 shrink-0 text-[#f2c94c]">
              <path d="M12 2 4 5v6c0 5.25 3.4 9.74 8 11 4.6-1.26 8-5.75 8-11V5l-8-3Z" stroke="currentColor" stroke-width="1.5" />
              <path d="M8 12h8M8 9h8M8 15h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
          </svg>
          <span class="self-center text-lg font-semibold leading-tight whitespace-nowrap text-white">
              CRUD <span class="text-[#f2c94c]">Agency</span>
          </span>
        </a>
      </div>
      <div class="flex items-center">
          <div class="flex items-center ms-3">
            <div>
              <button type="button" class="flex text-sm bg-[#0a1547] rounded-full ring-2 ring-transparent focus:ring-[#f2c94c]" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                <span class="sr-only">Open user menu</span>
                <img class="w-8 h-8 rounded-full" src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="user photo">
              </button>
            </div>
            <div class="z-50 hidden bg-white border border-gray-100 rounded-xl shadow-lg w-48" id="dropdown-user">
              <div class="px-4 py-3 border-b border-gray-100" role="none">
                <p class="text-sm font-medium text-gray-900" role="none">
                  Neil Sims
                </p>
                <p class="text-sm text-gray-500 truncate" role="none">
                  neil.sims@crudagency.com
                </p>
              </div>
              <ul class="p-2 text-sm font-medium text-gray-600" role="none">
                <li>
                  <a href="#" class="inline-flex items-center w-full p-2 hover:bg-[#f2c94c]/10 hover:text-[#020830] rounded-lg transition-colors" role="menuitem">Dashboard</a>
                </li>
                <li>
                  <a href="#" class="inline-flex items-center w-full p-2 hover:bg-[#f2c94c]/10 hover:text-[#020830] rounded-lg transition-colors" role="menuitem">Settings</a>
                </li>
                <li>
                  <a href="#" class="inline-flex items-center w-full p-2 hover:bg-[#f2c94c]/10 hover:text-[#020830] rounded-lg transition-colors" role="menuitem">Earnings</a>
                </li>
                <li>
                  <a href="#" class="inline-flex items-center w-full p-2 hover:bg-red-50 hover:text-red-600 rounded-lg transition-colors" role="menuitem">Sign out</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
    </div>
  </div>
</nav>
