@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Edit Quiz: {{ $quiz->title }}</h1>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Quiz Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.quizzes.update', $quiz->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="lesson_id" class="form-label">Lesson</label>
                            <select name="lesson_id" id="lesson_id" class="form-control @error('lesson_id') is-invalid @enderror" required>
                                @foreach($lessons as $lesson)
                                    <option value="{{ $lesson->id }}" {{ $quiz->lesson_id == $lesson->id ? 'selected' : '' }}>
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
                            <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $quiz->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $quiz->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="time_limit_minutes" class="form-label">Time Limit (minutes)</label>
                                    <input type="number" name="time_limit_minutes" id="time_limit_minutes" class="form-control @error('time_limit_minutes') is-invalid @enderror" value="{{ old('time_limit_minutes', $quiz->time_limit_minutes) }}" min="1" max="180" required>
                                    @error('time_limit_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="passing_score_percentage" class="form-label">Passing Score (%)</label>
                                    <input type="number" name="passing_score_percentage" id="passing_score_percentage" class="form-control @error('passing_score_percentage') is-invalid @enderror" value="{{ old('passing_score_percentage', $quiz->passing_score_percentage) }}" min="0" max="100" required>
                                    @error('passing_score_percentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="max_attempts" class="form-label">Max Attempts</label>
                                    <input type="number" name="max_attempts" id="max_attempts" class="form-control @error('max_attempts') is-invalid @enderror" value="{{ old('max_attempts', $quiz->max_attempts) }}" min="1" max="10" required>
                                    @error('max_attempts')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="randomize_questions" id="randomize_questions" class="form-check-input" value="1" {{ old('randomize_questions', $quiz->randomize_questions) ? 'checked' : '' }}>
                            <label for="randomize_questions" class="form-check-label">Randomize Questions</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Quiz</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Questions</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addQuestionModal">Add Question</button>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Question</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quiz->questions as $question)
                                <tr>
                                    <td>{{ $question->order }}</td>
                                    <td>{{ Str::limit($question->question_text, 50) }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</td>
                                    <td>{{ $question->points }}</td>
                                    <td>
                                        <form action="{{ route('admin.questions.destroy', $question->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No questions added yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.quizzes.questions.store', $quiz->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text</label>
                        <textarea name="question_text" id="question_text" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="question_type" class="form-label">Question Type</label>
                                <select name="question_type" id="question_type" class="form-control" required>
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="true_false">True/False</option>
                                    <option value="short_answer">Short Answer</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="points" class="form-label">Points</label>
                                <input type="number" name="points" id="points" class="form-control" min="1" value="1" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="order" class="form-label">Order</label>
                                <input type="number" name="order" id="order" class="form-control" min="1" value="{{ $quiz->questions->count() + 1 }}" required>
                            </div>
                        </div>
                    </div>

                    <div id="type-specific-fields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Question</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
