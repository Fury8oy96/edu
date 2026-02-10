@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Attempt Details</h1>

            @if(!empty($attemptDetails))
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>{{ $attemptDetails['quiz_title'] }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Student:</strong> {{ $attemptDetails['student_name'] }} ({{ $attemptDetails['student_email'] }})</p>
                                <p><strong>Started:</strong> {{ $attemptDetails['started_at']->format('Y-m-d H:i:s') }}</p>
                                <p><strong>Submitted:</strong> {{ $attemptDetails['submitted_at']->format('Y-m-d H:i:s') }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Time Taken:</strong> {{ $attemptDetails['time_taken_minutes'] }} minutes</p>
                                <p><strong>Score:</strong> {{ $attemptDetails['score_percentage'] }}%</p>
                                <p><strong>Status:</strong> 
                                    @if($attemptDetails['passed'])
                                        <span class="badge bg-success">Passed</span>
                                    @else
                                        <span class="badge bg-danger">Failed</span>
                                    @endif
                                    @if($attemptDetails['requires_grading'])
                                        <span class="badge bg-warning">Requires Grading</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Answers</h5>
                    </div>
                    <div class="card-body">
                        @foreach($attemptDetails['answers'] as $index => $answer)
                            <div class="mb-4 p-3 border rounded">
                                <h6>Question {{ $index + 1 }}: {{ $answer['question_text'] }}</h6>
                                <p><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $answer['question_type'])) }}</p>
                                <p><strong>Student Answer:</strong> {{ $answer['student_answer'] ?? 'No answer provided' }}</p>
                                
                                @if($answer['question_type'] !== 'short_answer')
                                    <p><strong>Correct Answer:</strong> {{ $answer['correct_answer'] }}</p>
                                @endif

                                <p><strong>Points:</strong> {{ $answer['points_awarded'] ?? 'Not graded' }} / {{ $answer['question_points'] }}</p>
                                
                                @if(!is_null($answer['is_correct']))
                                    <p><strong>Result:</strong> 
                                        @if($answer['is_correct'])
                                            <span class="badge bg-success">Correct</span>
                                        @else
                                            <span class="badge bg-danger">Incorrect</span>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="alert alert-warning">Attempt not found</div>
            @endif
        </div>
    </div>
</div>
@endsection
