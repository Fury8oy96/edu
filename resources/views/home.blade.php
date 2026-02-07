@extends('layouts.app')

@section('title', 'Home — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-4xl">
        <h1 class="mb-4 text-2xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
            Welcome
        </h1>
        <p class="text-[#706f6c] dark:text-[#A1A09A]">
            This page uses the app layout with side navigation. Add your content here or in other views that
            <code class="rounded bg-[#e3e3e0] dark:bg-[#3E3E3A] px-1.5 py-0.5 text-sm">@extends('layouts.app')</code>.
        </p>
    </div>
@endsection
