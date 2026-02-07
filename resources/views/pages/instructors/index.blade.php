@extends('layouts.app')

@section('title', 'Instructors — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-4xl">
        <h1 class="mb-4 text-2xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
            Instructors
        </h1>
    </div>
    <table class="table-auto w-full">
        <thead>
            <tr>
                <th class="px-4 py-2">Name</th>
                <th class="px-4 py-2">Email</th>
                <th class="px-4 py-2">Bio</th>
                <th class="px-4 py-2">Avatar</th>
            </tr>
        </thead>
    </table>
    <table class="table-auto w-full">
        <thead>
            <tr>
                @foreach ($instructors as $instructor)
                    <td class="px-4 py-2">{{ $instructor->name }}</td>
                    <td class="px-4 py-2">{{ $instructor->email }}</td>
                    <td class="px-4 py-2">{{ $instructor->bio }}</td>
                    <td class="px-4 py-2">{{ $instructor->avatar }}</td>
                @endforeach
            </tr>
        </thead>
    </table>
@endsection