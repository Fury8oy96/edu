<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateDetailResource extends JsonResource
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
            'student_email' => $this->student_email,
            'course_title' => $this->course_title,
            'instructor_name' => $this->instructor_name,
            'course_duration' => $this->course_duration,
            'completion_date' => $this->completion_date->format('Y-m-d'),
            'grade' => $this->grade,
            'average_score' => $this->average_score,
            'assessment_scores' => $this->assessment_scores,
            'status' => $this->status,
            'verification_url' => $this->verification_url,
            'pdf_url' => $this->pdf_path ? url("api/certificates/{$this->certificate_id}/download") : null,
            'issued_by' => $this->issued_by,
            'issued_at' => $this->created_at->format('Y-m-d H:i:s'),
            'revoked_at' => $this->revoked_at?->format('Y-m-d H:i:s'),
            'revocation_reason' => $this->revocation_reason,
        ];
    }
}
