<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'certificate_id' => $this->certificate_id,
            'student_name' => $this->student_name,
            'course_title' => $this->course_title,
            'completion_date' => $this->completion_date->format('Y-m-d'),
            'grade' => $this->grade,
            'status' => $this->status,
            'verification_url' => $this->verification_url,
            'pdf_url' => $this->pdf_path ? url("api/certificates/{$this->certificate_id}/download") : null,
        ];
    }
}
