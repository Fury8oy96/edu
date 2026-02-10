@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Assessments</h1>
                <a href="{{ route('admin.assessments.create') }}" class="btn btn-primary">Create Assessment</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card">
                <div class="card-body">
                    @forelse($assessments as $assessment)
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1">{{ $assessment->title }}</h5>
                                    <p class="mb-0 text-muted">Course: {{ $assessment->course->name }}</p>
                                </div>
                                <div>
                                    <span class="badge {{ $assessment->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $assessment->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <p>{{ $assessment->description }}</p>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Time Limit:</strong> {{ $assessment->time_limit }} minutes
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Passing Score:</strong> {{ $assessment->passing_score }}%
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Max Attempts:</strong> {{ $assessment->max_attempts ?? 'Unlimited' }}
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Questions:</strong> {{ $assessment->questions_count }}
                                    </div>
                                </div>
                                @if($assessment->start_date || $assessment->end_date)
                                    <div class="mt-2">
                                        <strong>Availability:</strong> 
                                        {{ $assessment->start_date?->format('Y-m-d') }} to {{ $assessment->end_date?->format('Y-m-d') }}
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer">
                                <a href="{{ route('admin.assessments.edit', $assessment->id) }}" class="btn btn-sm btn-primary">Edit</a>
                                <a href="{{ route('admin.assessments.analytics', $assessment->id) }}" class="btn btn-sm btn-info">Analytics</a>
                                <form action="{{ route('admin.assessments.destroy', $assessment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this assessment? This will remove all questions and attempts.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-center">No assessments found. <a href="{{ route('admin.assessments.create') }}">Create one now</a></p>
                    @endforelse

                    @if(isset($assessments) && method_exists($assessments, 'links'))
                        <div class="mt-3">
                            {{ $assessments->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
