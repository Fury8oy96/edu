<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'time_limit_minutes' => $this->time_limit_minutes,
            'passing_score_percentage' => $this->passing_score_percentage,
            'max_attempts' => $this->max_attempts,
            'randomize_questions' => $this->randomize_questions,
            'total_points' => $this->total_points,
            'lesson' => [
                'id' => $this->lesson->id,
                'title' => $this->lesson->title,
            ],
            'questions_count' => $this->questions->count(),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
