@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Grade Assessment Attempt</h1>
                <a href="{{ route('admin.assessment-grading.pending') }}" class="btn btn-secondary">Back to Queue</a>
            </div>

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

            <!-- Attempt Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Attempt Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Assessment:</strong> {{ $attempt->assessment->title }}</p>
                            <p><strong>Student:</strong> {{ $attempt->student->name }} ({{ $attempt->student->email }})</p>
                            <p><strong>Attempt Number:</strong> {{ $attempt->attempt_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Submitted:</strong> {{ $attempt->completion_time?->format('Y-m-d H:i:s') }}</p>
                            <p><strong>Time Taken:</strong> {{ $attempt->time_taken ? round($attempt->time_taken / 60, 2) . ' minutes' : 'N/A' }}</p>
                            <p><strong>Current Score:</strong> {{ $attempt->score ?? 0 }} / {{ $attempt->max_score }} ({{ number_format($attempt->percentage ?? 0, 2) }}%)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions and Answers -->
            @foreach($attempt->answers as $answer)
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Question {{ $answer->question->order }}</h6>
                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $answer->question->question_type)) }}</span>
                            <span class="badge bg-secondary">{{ $answer->question->points }} points</span>
                            @if($answer->grading_status === 'auto_graded')
                                <span class="badge bg-success">Auto-graded</span>
                            @elseif($answer->grading_status === 'manually_graded')
                                <span class="badge bg-success">Manually graded</span>
                            @else
                                <span class="badge bg-warning">Pending review</span>
                            @endif
                        </div>
                        @if($answer->points_earned !== null)
                            <div>
                                <strong>Score:</strong> {{ $answer->points_earned }} / {{ $answer->question->points }}
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <p><strong>Question:</strong> {{ $answer->question->question_text }}</p>
                        
                        @if($answer->question->question_type === 'multiple_choice')
                            <p><strong>Student Answer:</strong> 
                                @php
                                    $studentAnswer = $answer->answer['selected_option_id'] ?? null;
                                    $options = $answer->question->options ?? [];
                                    $selectedOption = collect($options)->firstWhere('id', $studentAnswer);
                                @endphp
                                {{ $selectedOption['text'] ?? 'No answer' }}
                            </p>
                            @if($answer->is_correct !== null)
                                <p><strong>Result:</strong> 
                                    <span class="badge {{ $answer->is_correct ? 'bg-success' : 'bg-danger' }}">
                                        {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                                    </span>
                                </p>
                            @endif
                        @elseif($answer->question->question_type === 'true_false')
                            <p><strong>Student Answer:</strong> {{ ($answer->answer['value'] ?? null) ? 'True' : 'False' }}</p>
                            @if($answer->is_correct !== null)
                                <p><strong>Result:</strong> 
                                    <span class="badge {{ $answer->is_correct ? 'bg-success' : 'bg-danger' }}">
                                        {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                                    </span>
                                </p>
                            @endif
                        @elseif(in_array($answer->question->question_type, ['short_answer', 'essay']))
                            <div class="mb-3">
                                <strong>Student Answer:</strong>
                                <div class="p-3 bg-light border rounded">
                                    {{ $answer->answer['text'] ?? 'No answer provided' }}
                                </div>
                            </div>

                            @if($answer->question->grading_rubric)
                                <div class="mb-3">
                                    <strong>Grading Rubric:</strong>
                                    <div class="p-3 bg-light border rounded">
                                        {{ $answer->question->grading_rubric }}
                                    </div>
                                </div>
                            @endif

                            @if($answer->grading_status === 'pending_review')
                                <form action="{{ route('admin.assessment-grading.update', $answer->id) }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="points_earned_{{ $answer->id }}" class="form-label">Points Earned *</label>
                                            <input type="number" name="points_earned" id="points_earned_{{ $answer->id }}" class="form-control" min="0" max="{{ $answer->question->points }}" step="0.01" required>
                                            <small class="text-muted">Max: {{ $answer->question->points }} points</small>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label for="grader_feedback_{{ $answer->id }}" class="form-label">Feedback (optional)</label>
                                            <textarea name="grader_feedback" id="grader_feedback_{{ $answer->id }}" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Grade</button>
                                </form>
                            @else
                                <div class="alert alert-success">
                                    <p class="mb-0"><strong>Graded:</strong> {{ $answer->points_earned }} / {{ $answer->question->points }} points</p>
                                    @if($answer->grader_feedback)
                                        <p class="mb-0"><strong>Feedback:</strong> {{ $answer->grader_feedback }}</p>
                                    @endif
                                    @if($answer->graded_at)
                                        <p class="mb-0"><small>Graded on {{ $answer->graded_at->format('Y-m-d H:i:s') }}</small></p>
                                    @endif
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Final Score Summary -->
            <div class="card">
                <div class="card-header">
                    <h5>Score Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Total Score:</strong> {{ $attempt->score ?? 0 }} / {{ $attempt->max_score }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Percentage:</strong> {{ number_format($attempt->percentage ?? 0, 2) }}%</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Status:</strong> 
                                @if($attempt->passed === true)
                                    <span class="badge bg-success">Passed</span>
                                @elseif($attempt->passed === false)
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-warning">Pending</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <p class="mb-0"><strong>Passing Score:</strong> {{ $attempt->assessment->passing_score }}%</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
