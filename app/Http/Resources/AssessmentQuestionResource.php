<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssessmentQuestionResource extends JsonResource
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
            'assessment_id' => $this->assessment_id,
            'question_type' => $this->question_type,
            'question_text' => $this->question_text,
            'points' => $this->points,
            'order' => $this->order,
            'options' => $this->when(
                $this->question_type === 'multiple_choice',
                function () {
                    // Remove is_correct flag from options for students
                    return collect($this->options)->map(function ($option) {
                        return [
                            'id' => $option['id'] ?? null,
                            'text' => $option['text'] ?? null,
                        ];
                    });
                }
            ),
            'grading_rubric' => $this->when(
                in_array($this->question_type, ['short_answer', 'essay']),
                $this->grading_rubric
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
