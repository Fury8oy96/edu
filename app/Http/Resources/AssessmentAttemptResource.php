<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assessment' => [
                'id' => $this->assessment->id,
                'title' => $this->assessment->title,
                'course_name' => $this->whenLoaded('assessment.course', fn() => $this->assessment->course->name),
            ],
            'attempt_number' => $this->attempt_number,
            'status' => $this->status,
            'start_time' => $this->start_time->toIso8601String(),
            'completion_time' => $this->completion_time?->toIso8601String(),
            'time_taken' => $this->time_taken,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'percentage' => $this->percentage,
            'passed' => $this->passed,
            'answers' => AssessmentAnswerResource::collection($this->whenLoaded('answers')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
