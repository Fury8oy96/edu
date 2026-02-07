@extends('layouts.app')

@section('title', 'Dashboard — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-4xl">
        <h1 class="mb-4 text-2xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
            Dashboard
        </h1>
        <p class="text-[#706f6c] dark:text-[#A1A09A]">
            Welcome to the admin dashboard. You are signed in.
        </p>
    </div>
@endsection
