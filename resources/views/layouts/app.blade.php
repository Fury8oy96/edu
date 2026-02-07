<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] font-calibri">
    <div class="flex min-h-screen">
        {{-- Side navigation --}}
        <aside
            id="sidebar"
            class="fixed inset-y-0 left-0 z-40 w-58 flex flex-col border-r border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.06)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed14] transition-transform duration-200 ease-in-out lg:translate-x-0"
            aria-label="Sidebar"
        >
            {{-- Brand / logo --}}
            <div class="flex h-14 shrink-0 items-center gap-2 border-b border-[#e3e3e0] dark:border-[#3E3E3A] px-4">
                <a href="{{ url('/') }}" class="flex items-center gap-2 font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                    @yield('sidebar-brand', config('app.name'))
                </a>
            </div>

            {{-- Nav links: edit resources/views/layouts/partials/sidebar.blade.php to customize --}}
            <nav class="flex flex-1 flex-col overflow-y-auto p-4" aria-label="Main navigation">
                @hasSection('sidebar-nav')
                    @yield('sidebar-nav')
                @else
                    @include('layouts.partials.sidebar')
                @endif
            </nav>
        </aside>

        {{-- Main content --}}
        <main class="flex-1 lg:pl-64">
            <div class="py-6 px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
