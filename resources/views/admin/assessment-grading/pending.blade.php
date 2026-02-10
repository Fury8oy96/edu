@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Pending Assessment Grading</h1>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    @forelse($attempts as $attempt)
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5>{{ $attempt->assessment->title }}</h5>
                                <p class="mb-0">Student: {{ $attempt->student->name }} ({{ $attempt->student->email }})</p>
                                <p class="mb-0">Submitted: {{ $attempt->completion_time?->format('Y-m-d H:i:s') }}</p>
                                <p class="mb-0">Attempt #{{ $attempt->attempt_number }}</p>
                            </div>
                            <div class="card-body">
                                <p><strong>Pending Questions:</strong> {{ $attempt->answers->where('grading_status', 'pending_review')->count() }}</p>
                                <p><strong>Current Score:</strong> {{ $attempt->score ?? 0 }} / {{ $attempt->max_score }} ({{ number_format($attempt->percentage ?? 0, 2) }}%)</p>
                                
                                <a href="{{ route('admin.assessment-grading.show', $attempt->id) }}" class="btn btn-primary">
                                    Grade Attempt
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="text-center">No attempts pending grading</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
