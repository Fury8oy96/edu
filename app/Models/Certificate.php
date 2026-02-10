<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Certificate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'certificate_id',
        'student_id',
        'course_id',
        'student_name',
        'student_email',
        'course_title',
        'instructor_name',
        'course_duration',
        'completion_date',
        'grade',
        'average_score',
        'assessment_scores',
        'verification_url',
        'pdf_path',
        'issued_by',
        'issued_by_admin_id',
        'status',
        'revoked_at',
        'revoked_by_admin_id',
        'revocation_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completion_date' => 'datetime',
        'assessment_scores' => 'array',
        'revoked_at' => 'datetime',
        'average_score' => 'decimal:2',
    ];

    /**
     * Get the student that owns the certificate.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    /**
     * Get the course that the certificate is for.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Courses::class, 'course_id');
    }

    /**
     * Get the admin who issued the certificate (if manually issued).
     */
    public function issuedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_admin_id');
    }

    /**
     * Get the admin who revoked the certificate (if revoked).
     */
    public function revokedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_admin_id');
    }

    /**
     * Scope a query to only include active certificates.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Scope a query to only include revoked certificates.
     */
    public function scopeRevoked(Builder $query): void
    {
        $query->where('status', 'revoked');
    }

    /**
     * Scope a query to filter certificates by grade.
     */
    public function scopeByGrade(Builder $query, string $grade): void
    {
        $query->where('grade', $grade);
    }

    /**
     * Scope a query to filter certificates by date range.
     *
     * @param Builder $query
     * @param \Carbon\Carbon|string|null $start
     * @param \Carbon\Carbon|string|null $end
     */
    public function scopeByDateRange(Builder $query, $start, $end): void
    {
        if ($start) {
            $query->where('completion_date', '>=', $start);
        }
        
        if ($end) {
            $query->where('completion_date', '<=', $end);
        }
    }

    /**
     * Get whether the certificate is revoked.
     */
    public function getIsRevokedAttribute(): bool
    {
        return $this->status === 'revoked';
    }

    /**
     * Get whether the certificate is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
