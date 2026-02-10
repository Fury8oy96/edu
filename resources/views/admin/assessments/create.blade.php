@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Create Assessment</h1>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.assessments.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course *</label>
                            <select name="course_id" id="course_id" class="form-control" required>
                                <option value="">Select a course</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="time_limit" class="form-label">Time Limit (minutes) *</label>
                                <input type="number" name="time_limit" id="time_limit" class="form-control" value="{{ old('time_limit', 60) }}" min="1" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="passing_score" class="form-label">Passing Score (%) *</label>
                                <input type="number" name="passing_score" id="passing_score" class="form-control" value="{{ old('passing_score', 70) }}" min="0" max="100" step="0.01" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="max_attempts" class="form-label">Max Attempts</label>
                                <input type="number" name="max_attempts" id="max_attempts" class="form-control" value="{{ old('max_attempts') }}" min="1" placeholder="Leave empty for unlimited">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="datetime-local" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="datetime-local" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.assessments.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Assessment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
