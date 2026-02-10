@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Quizzes</h1>
                <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary">Create New Quiz</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Lesson</th>
                                <th>Course</th>
                                <th>Time Limit</th>
                                <th>Passing Score</th>
                                <th>Max Attempts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quizzes as $quiz)
                                <tr>
                                    <td>{{ $quiz->id }}</td>
                                    <td>{{ $quiz->title }}</td>
                                    <td>{{ $quiz->lesson->title }}</td>
                                    <td>{{ $quiz->lesson->module->course->title }}</td>
                                    <td>{{ $quiz->time_limit_minutes }} min</td>
                                    <td>{{ $quiz->passing_score_percentage }}%</td>
                                    <td>{{ $quiz->max_attempts }}</td>
                                    <td>
                                        <a href="{{ route('admin.quizzes.edit', $quiz->id) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <a href="{{ route('admin.quizzes.analytics', $quiz->id) }}" class="btn btn-sm btn-info">Analytics</a>
                                        <form action="{{ route('admin.quizzes.destroy', $quiz->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No quizzes found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $quizzes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
