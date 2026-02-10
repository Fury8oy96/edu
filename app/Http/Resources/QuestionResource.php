<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'question_text' => $this->question_text,
            'question_type' => $this->question_type,
            'points' => $this->points,
            'order' => $this->order,
        ];

        // Include options for multiple_choice but hide correct answers
        if ($this->question_type === 'multiple_choice' && is_array($this->options)) {
            $data['options'] = array_map(function ($option) {
                return [
                    'text' => $option['text'] ?? '',
                ];
            }, $this->options);
        }

        return $data;
    }
}
