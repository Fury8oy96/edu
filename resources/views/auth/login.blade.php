@extends('layouts.auth')

@section('title', 'Log in — ' . config('app.name'))

@section('content')
    <div class="w-full max-w-sm space-y-8 rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-8 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.06)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed14]">
        <div>
            <h1 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                Admin sign in
            </h1>
            <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                Sign in to access the dashboard.
            </p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-6">
            @csrf

            @if ($errors->any())
                <div class="rounded-md border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-950/30 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="mt-1 block w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-[#FDFDFC] dark:bg-[#0a0a0a] px-3 py-2 text-[#1b1b18] dark:text-[#EDEDEC] shadow-sm focus:border-[#1b1b18] dark:focus:border-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#1b1b18] dark:focus:ring-[#EDEDEC] sm:text-sm"
                    />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="mt-1 block w-full rounded-md border border-[#e3e3e0] dark:border-[#3E3E3A] bg-[#FDFDFC] dark:bg-[#0a0a0a] px-3 py-2 text-[#1b1b18] dark:text-[#EDEDEC] shadow-sm focus:border-[#1b1b18] dark:focus:border-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#1b1b18] dark:focus:ring-[#EDEDEC] sm:text-sm"
                    />
                </div>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        name="remember"
                        class="rounded border-[#e3e3e0] dark:border-[#3E3E3A] text-[#1b1b18] dark:text-[#EDEDEC] focus:ring-[#1b1b18] dark:focus:ring-[#EDEDEC]"
                    />
                    <span class="ml-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Remember me</span>
                </label>
            </div>

            <div>
                <button
                    type="submit"
                    class="flex w-full justify-center rounded-md border border-transparent bg-[#1b1b18] dark:bg-[#EDEDEC] px-4 py-2 text-sm font-medium text-white dark:text-[#1b1b18] hover:bg-[#2d2d2a] dark:hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#1b1b18] dark:focus:ring-[#EDEDEC] focus:ring-offset-2"
                >
                    Sign in
                </button>
            </div>
        </form>
    </div>
@endsection
