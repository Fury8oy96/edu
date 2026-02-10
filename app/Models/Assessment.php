<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'time_limit',
        'passing_score',
        'max_attempts',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'time_limit' => 'integer',
        'passing_score' => 'decimal:2',
        'max_attempts' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the course that this assessment belongs to
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Courses::class);
    }

    /**
     * Get all questions for this assessment
     */
    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class);
    }

    /**
     * Get all attempts for this assessment
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    /**
     * Get all prerequisites for this assessment
     */
    public function prerequisites(): HasMany
    {
        return $this->hasMany(AssessmentPrerequisite::class);
    }

    /**
     * Calculate attempts remaining for a student
     * 
     * @param Students $student
     * @return int|null null means unlimited
     */
    public function calculateAttemptsRemaining(Students $student): ?int
    {
        if ($this->max_attempts === null) {
            return null; // Unlimited attempts
        }

        $completedAttempts = $this->attempts()
            ->where('student_id', $student->id)
            ->whereIn('status', ['completed', 'timed_out', 'grading_pending'])
            ->count();

        $remaining = $this->max_attempts - $completedAttempts;
        return max(0, $remaining);
    }

    /**
     * Check if student meets all prerequisites
     * 
     * @param Students $student
     * @return bool
     */
    public function checkPrerequisites(Students $student): bool
    {
        // If no prerequisites, access is granted
        if ($this->prerequisites()->count() === 0) {
            return true;
        }

        // This is a placeholder - actual implementation will be in PrerequisiteCheckService
        // For now, return true to allow development
        return true;
    }
}
