@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Pending Grading Queue</h1>

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
                                <h5>{{ $attempt->quiz->title }}</h5>
                                <p class="mb-0">Student: {{ $attempt->student->name }} ({{ $attempt->student->email }})</p>
                                <p class="mb-0">Submitted: {{ $attempt->submitted_at->format('Y-m-d H:i:s') }}</p>
                            </div>
                            <div class="card-body">
                                @foreach($attempt->answers as $answer)
                                    @if($answer->question->question_type === 'short_answer' && is_null($answer->points_awarded))
                                        <div class="mb-4 p-3 border rounded">
                                            <h6>{{ $answer->question->question_text }}</h6>
                                            <p><strong>Student Answer:</strong> {{ $answer->student_answer ?? 'No answer provided' }}</p>
                                            <p><strong>Max Points:</strong> {{ $answer->question->points }}</p>

                                            <form action="{{ route('admin.grading.grade', $answer->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <div class="input-group" style="max-width: 300px;">
                                                    <input type="number" name="points_awarded" class="form-control" min="0" max="{{ $answer->question->points }}" step="0.5" placeholder="Points" required>
                                                    <button type="submit" class="btn btn-primary">Grade</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                @endforeach
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
