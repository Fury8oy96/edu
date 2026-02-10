<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
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
            'start_time' => $this->start_time->toIso8601String(),
            'end_time' => $this->end_time->toIso8601String(),
            'state' => $this->state,
            'max_participants' => $this->max_participants,
            'registration_count' => $this->registration_count,
            'participation_count' => $this->participation_count,
            'attendance_count' => $this->attendance_count,
            'has_capacity' => $this->hasCapacity(),
        ];
    }
}
