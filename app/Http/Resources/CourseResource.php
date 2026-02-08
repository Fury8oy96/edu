<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'enrollment_count' => $this->enrollment_count,
            'created_at' => $this->created_at,
        ];
    }
}
