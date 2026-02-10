@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Create New Quiz</h1>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.quizzes.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="lesson_id" class="form-label">Lesson</label>
                            <select name="lesson_id" id="lesson_id" class="form-control @error('lesson_id') is-invalid @enderror" required>
                                <option value="">Select a lesson</option>
                                @foreach($lessons as $lesson)
                                    <option value="{{ $lesson->id }}" {{ old('lesson_id') == $lesson->id ? 'selected' : '' }}>
                                        {{ $lesson->module->course->title }} - {{ $lesson->module->title }} - {{ $lesson->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('lesson_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Quiz Title</label>
                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="time_limit_minutes" class="form-label">Time Limit (minutes)</label>
                                    <input type="number" name="time_limit_minutes" id="time_limit_minutes" class="form-control @error('time_limit_minutes') is-invalid @enderror" value="{{ old('time_limit_minutes', 30) }}" min="1" max="180" required>
                                    @error('time_limit_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="passing_score_percentage" class="form-label">Passing Score (%)</label>
                                    <input type="number" name="passing_score_percentage" id="passing_score_percentage" class="form-control @error('passing_score_percentage') is-invalid @enderror" value="{{ old('passing_score_percentage', 70) }}" min="0" max="100" required>
                                    @error('passing_score_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="max_attempts" class="form-label">Max Attempts</label>
                                    <input type="number" name="max_attempts" id="max_attempts" class="form-control @error('max_attempts') is-invalid @enderror" value="{{ old('max_attempts', 3) }}" min="1" max="10" required>
                                    @error('max_attempts')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="randomize_questions" id="randomize_questions" class="form-check-input" value="1" {{ old('randomize_questions') ? 'checked' : '' }}>
                            <label for="randomize_questions" class="form-check-label">Randomize Questions</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
