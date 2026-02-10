@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Assessment Analytics</h1>
                <div>
                    <a href="{{ route('admin.assessments.analytics.export', $assessmentId) }}" class="btn btn-success">
                        Export CSV
                    </a>
                    <a href="{{ route('admin.assessments.index') }}" class="btn btn-secondary">
                        Back to Assessments
                    </a>
                </div>
            </div>

            <!-- Overall Statistics -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>{{ $analytics['total_attempts'] ?? 0 }}</h3>
                            <p class="text-muted mb-0">Total Attempts</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>{{ number_format($analytics['average_score'] ?? 0, 2) }}%</h3>
                            <p class="text-muted mb-0">Average Score</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>{{ number_format($analytics['pass_rate'] ?? 0, 2) }}%</h3>
                            <p class="text-muted mb-0">Pass Rate</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question Statistics -->
            @if(!empty($analytics['question_statistics']))
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Question Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Question</th>
                                        <th>Type</th>
                                        <th>Max Points</th>
                                        <th>Average Score</th>
                                        <th>Success Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analytics['question_statistics'] as $stat)
                                        <tr>
                                            <td>{{ $stat['question_id'] ?? '' }}</td>
                                            <td>{{ Str::limit($stat['question_text'] ?? '', 100) }}</td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ ucfirst(str_replace('_', ' ', $stat['question_type'] ?? '')) }}
                                                </span>
                                            </td>
                                            <td>{{ $stat['max_points'] ?? 0 }}</td>
                                            <td>{{ number_format($stat['average_score'] ?? 0, 2) }}</td>
                                            <td>
                                                @php
                                                    $successRate = isset($stat['average_score'], $stat['max_points']) && $stat['max_points'] > 0
                                                        ? ($stat['average_score'] / $stat['max_points']) * 100
                                                        : 0;
                                                @endphp
                                                <span class="badge {{ $successRate >= 70 ? 'bg-success' : ($successRate >= 50 ? 'bg-warning' : 'bg-danger') }}">
                                                    {{ number_format($successRate, 1) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Student Attempts (if filtered by student) -->
            @if(!empty($analytics['student_attempts']))
                <div class="card">
                    <div class="card-header">
                        <h5>Student Attempt History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Attempt #</th>
                                        <th>Date</th>
                                        <th>Score</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                        <th>Time Taken</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analytics['student_attempts'] as $attempt)
                                        <tr>
                                            <td>{{ $attempt['attempt_number'] ?? '' }}</td>
                                            <td>{{ $attempt['completion_time'] ?? '' }}</td>
                                            <td>{{ number_format($attempt['score'] ?? 0, 2) }} / {{ number_format($attempt['max_score'] ?? 0, 2) }}</td>
                                            <td>{{ number_format($attempt['percentage'] ?? 0, 2) }}%</td>
                                            <td>
                                                @if($attempt['passed'] ?? false)
                                                    <span class="badge bg-success">Passed</span>
                                                @else
                                                    <span class="badge bg-danger">Failed</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(isset($attempt['time_taken']))
                                                    {{ round($attempt['time_taken'] / 60, 2) }} min
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recent Attempts -->
            @if(!empty($analytics['recent_attempts']))
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Recent Attempts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Date</th>
                                        <th>Score</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analytics['recent_attempts'] as $attempt)
                                        <tr>
                                            <td>{{ $attempt['student_name'] ?? '' }}</td>
                                            <td>{{ $attempt['completion_time'] ?? '' }}</td>
                                            <td>{{ number_format($attempt['percentage'] ?? 0, 2) }}%</td>
                                            <td>
                                                @if($attempt['passed'] ?? false)
                                                    <span class="badge bg-success">Passed</span>
                                                @else
                                                    <span class="badge bg-danger">Failed</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
