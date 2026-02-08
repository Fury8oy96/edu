<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolledCourseResource extends JsonResource
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
            'price' => $this->price,
            'duration_hours' => $this->duration_hours,
            'level' => $this->level,
            'category' => $this->category,
            'instructor' => new InstructorResource($this->whenLoaded('instructor')),
            'enrollment' => [
                'enrolled_at' => $this->pivot->enrolled_at,
                'status' => $this->pivot->status,
                'progress_percentage' => $this->pivot->progress_percentage,
            ],
        ];
    }
}
