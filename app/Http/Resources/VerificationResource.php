<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'certificate_id' => $this->resource['certificate_id'],
            'student_name' => $this->resource['student_name'],
            'course_title' => $this->resource['course_title'],
            'completion_date' => $this->resource['completion_date'],
            'grade' => $this->resource['grade'],
            'status' => $this->resource['status'],
            'verified' => $this->resource['verified'],
        ];
    }
}
