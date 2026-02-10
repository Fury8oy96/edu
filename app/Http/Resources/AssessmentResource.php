<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $request->user();
        
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course_name' => $this->whenLoaded('course', fn() => $this->course->name),
            'title' => $this->title,
            'description' => $this->description,
            'time_limit' => $this->time_limit,
            'passing_score' => $this->passing_score,
            'max_attempts' => $this->max_attempts,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'is_active' => $this->is_active,
            'questions_count' => $this->whenCounted('questions'),
            'total_points' => $this->whenLoaded('questions', fn() => $this->questions->sum('points')),
            'attempts_remaining' => $this->when($student, fn() => $this->calculateAttemptsRemaining($student)),
            'prerequisites_met' => $this->when($student, fn() => $this->checkPrerequisites($student)),
            'questions' => AssessmentQuestionResource::collection($this->whenLoaded('questions')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
