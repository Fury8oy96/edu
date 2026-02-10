<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentAnswerResource extends JsonResource
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
            'question' => $this->when(
                $this->relationLoaded('question'),
                function () {
                    return [
                        'id' => $this->question->id,
                        'question_text' => $this->question->question_text,
                        'question_type' => $this->question->question_type,
                        'points' => $this->question->points,
                        'correct_answer' => $this->when(
                            $this->grading_status !== 'pending_review',
                            $this->question->correct_answer
                        ),
                    ];
                }
            ),
            'answer' => $this->answer,
            'is_correct' => $this->when($this->is_correct !== null, $this->is_correct),
            'points_earned' => $this->points_earned,
            'grading_status' => $this->grading_status,
            'grader_feedback' => $this->when($this->grader_feedback !== null, $this->grader_feedback),
            'graded_at' => $this->when($this->graded_at !== null, $this->graded_at->toIso8601String()),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
