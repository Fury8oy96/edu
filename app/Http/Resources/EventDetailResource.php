<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseData = [
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

        // Add student status if available
        if (isset($this->student_status)) {
            $baseData['student_status'] = $this->student_status;
        }

        return $baseData;
    }
}
