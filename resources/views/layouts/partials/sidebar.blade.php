{{-- Admin sidebar: no registration, logout only. --}}
<ul class="flex flex-1 flex-col space-y-1">
    <li>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-[#706f6c] dark:text-[#A1A09A] hover:bg-[#FDFDFC] hover:text-[#1b1b18] dark:hover:bg-[#0a0a0a] dark:hover:text-[#EDEDEC] {{ request()->routeIs('dashboard') ? 'bg-[#FDFDFC] dark:bg-[#0a0a0a] font-medium text-[#1b1b18] dark:text-[#EDEDEC]' : '' }}">
            <span>Dashboard</span>
        </a>
    </li>
    <li class="mt-auto border-t border-[#e3e3e0] dark:border-[#3E3E3A] pt-4">
        <form method="POST" action="{{ route('logout') }}" class="block">
            @csrf
            <h5 class="px-1">{{ auth()->user()->name }}</h5>
            <button type="submit" class="flex w-full items-center gap-3 rounded-md px-1 py-2 text-left text-bold text-[#464646] dark:text-[#A1A09A] hover:bg-[#ffffff] hover:text-[#1b1b18] dark:hover:bg-[#0a0a0a] dark:hover:text-[#EDEDEC]">
                <h4>Sign Out</h4>
            </button>
        </form>
    </li>
</ul>
