@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Edit Assessment: {{ $assessment->title }}</h1>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Assessment Details Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Assessment Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.assessments.update', $assessment->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course *</label>
                            <select name="course_id" id="course_id" class="form-control" required>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ $assessment->course_id == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $assessment->title) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $assessment->description) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="time_limit" class="form-label">Time Limit (minutes) *</label>
                                <input type="number" name="time_limit" id="time_limit" class="form-control" value="{{ old('time_limit', $assessment->time_limit) }}" min="1" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="passing_score" class="form-label">Passing Score (%) *</label>
                                <input type="number" name="passing_score" id="passing_score" class="form-control" value="{{ old('passing_score', $assessment->passing_score) }}" min="0" max="100" step="0.01" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="max_attempts" class="form-label">Max Attempts</label>
                                <input type="number" name="max_attempts" id="max_attempts" class="form-control" value="{{ old('max_attempts', $assessment->max_attempts) }}" min="1" placeholder="Leave empty for unlimited">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="datetime-local" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', $assessment->start_date?->format('Y-m-d\TH:i')) }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="datetime-local" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', $assessment->end_date?->format('Y-m-d\TH:i')) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', $assessment->is_active) ? 'checked' : '' }}>
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.assessments.index') }}" class="btn btn-secondary">Back to List</a>
                            <button type="submit" class="btn btn-primary">Update Assessment</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Questions Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Questions</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        Add Question
                    </button>
                </div>
                <div class="card-body">
                    @forelse($assessment->questions()->orderBy('order')->get() as $question)
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="flex-grow-1">
                                        <h6>{{ $question->order }}. {{ $question->question_text }}</h6>
                                        <p class="mb-1">
                                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</span>
                                            <span class="badge bg-secondary">{{ $question->points }} points</span>
                                        </p>
                                    </div>
                                    <div>
                                        <form action="{{ route('admin.assessment-questions.destroy', $question->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted">No questions added yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Prerequisites Section -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Prerequisites</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPrerequisiteModal">
                        Add Prerequisite
                    </button>
                </div>
                <div class="card-body">
                    @forelse($assessment->prerequisites as $prerequisite)
                        <div class="card mb-2">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $prerequisite->prerequisite_type)) }}</strong>
                                    <p class="mb-0 text-muted small">{{ json_encode($prerequisite->prerequisite_data) }}</p>
                                </div>
                                <form action="{{ route('admin.assessment-prerequisites.destroy', $prerequisite->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted">No prerequisites set</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.assessments.questions.store', $assessment->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="question_type" class="form-label">Question Type *</label>
                        <select name="question_type" id="question_type" class="form-control" required>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True/False</option>
                            <option value="short_answer">Short Answer</option>
                            <option value="essay">Essay</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text *</label>
                        <textarea name="question_text" id="question_text" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="points" class="form-label">Points *</label>
                        <input type="number" name="points" id="points" class="form-control" min="0.01" step="0.01" value="1" required>
                    </div>

                    <div id="question_type_fields">
                        <!-- Dynamic fields based on question type will be added here via JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Prerequisite Modal -->
<div class="modal fade" id="addPrerequisiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.assessments.prerequisites.store', $assessment->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Prerequisite</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="prerequisite_type" class="form-label">Prerequisite Type *</label>
                        <select name="prerequisite_type" id="prerequisite_type" class="form-control" required>
                            <option value="quiz_completion">Quiz Completion</option>
                            <option value="minimum_progress">Minimum Progress</option>
                            <option value="lesson_completion">Lesson Completion</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Configuration *</label>
                        <textarea name="prerequisite_data" class="form-control" rows="3" placeholder='{"minimum_percentage": 75}' required></textarea>
                        <small class="text-muted">Enter JSON configuration for the prerequisite</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Prerequisite</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
