<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'event_id' => $this->event_id,
            'participation_start' => $this->participation_start->toIso8601String(),
            'event_end' => $this->event_end->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'event' => new EventResource($this->whenLoaded('event')),
        ];
    }
}
