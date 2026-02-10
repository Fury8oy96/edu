@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Quiz Analytics: {{ $quiz->title }}</h1>
            <p class="text-muted">Lesson: {{ $quiz->lesson->title }}</p>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>{{ $statistics['total_attempts'] }}</h3>
                            <p class="mb-0">Total Attempts</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>{{ $statistics['average_score'] }}%</h3>
                            <p class="mb-0">Average Score</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>{{ $statistics['pass_rate'] }}%</h3>
                            <p class="mb-0">Pass Rate</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>{{ $statistics['passed_count'] }}/{{ $statistics['total_attempts'] }}</h3>
                            <p class="mb-0">Passed/Total</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Student Results</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Best Score</th>
                                <th>Attempts</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($studentResults as $result)
                                <tr>
                                    <td>{{ $result['student_name'] }}</td>
                                    <td>{{ $result['student_email'] }}</td>
                                    <td>{{ $result['best_score'] }}%</td>
                                    <td>{{ $result['attempt_count'] }}</td>
                                    <td>
                                        @if($result['has_passed'])
                                            <span class="badge bg-success">Passed</span>
                                        @else
                                            <span class="badge bg-danger">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No student results yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
