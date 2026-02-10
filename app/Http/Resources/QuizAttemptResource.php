<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
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
            'quiz_id' => $this->quiz_id,
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'deadline' => $this->deadline,
            'remaining_seconds' => $this->when(
                !$this->isSubmitted(),
                $this->getRemainingTimeSeconds()
            ),
            'score' => $this->score,
            'score_percentage' => $this->score_percentage,
            'passed' => $this->passed,
            'requires_grading' => $this->requires_grading,
            'time_taken_minutes' => $this->time_taken_minutes,
            'quiz' => new QuizResource($this->whenLoaded('quiz')),
            'answers' => QuizAnswerResource::collection($this->whenLoaded('answers')),
        ];
    }
}
