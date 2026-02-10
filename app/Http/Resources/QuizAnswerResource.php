<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAnswerResource extends JsonResource
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
            'question_id' => $this->question_id,
            'question_text' => $this->question->question_text,
            'question_type' => $this->question->question_type,
            'student_answer' => $this->student_answer,
            'points_awarded' => $this->points_awarded,
            'is_correct' => $this->is_correct,
            'max_points' => $this->question->points,
        ];
    }
}
