<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'course_id' => $this->id,
            'course_name' => $this->title,
            'course_description' => $this->description,
            'enrollment' => [
                'enrolled_at' => \Carbon\Carbon::parse($this->pivot->enrolled_at)->toISOString(),
                'status' => $this->pivot->status,
                'progress_percentage' => $this->pivot->progress_percentage,
                'is_completed' => $this->pivot->progress_percentage === 100,
            ],
        ];
    }
}
