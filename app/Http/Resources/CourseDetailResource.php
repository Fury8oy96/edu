<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CourseDetailResource extends CourseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'requirements' => $this->requirements,
            'outcomes' => $this->outcomes,
            'target_audience' => $this->target_audience,
            'modules' => ModuleResource::collection($this->whenLoaded('modules')),
        ]);
    }
}
