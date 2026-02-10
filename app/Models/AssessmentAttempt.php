<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'student_id',
        'attempt_number',
        'status',
        'start_time',
        'completion_time',
        'time_taken',
        'score',
        'max_score',
        'percentage',
        'passed',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'start_time' => 'datetime',
        'completion_time' => 'datetime',
        'time_taken' => 'integer',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'passed' => 'boolean',
    ];

    /**
     * Get the assessment that this attempt belongs to
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Get the student who made this attempt
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class);
    }

    /**
     * Get all answers for this attempt
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class, 'attempt_id');
    }

    /**
     * Check if attempt is in progress
     * 
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if attempt is completed
     * 
     * @return bool
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'timed_out', 'grading_pending']);
    }

    /**
     * Check if attempt has timed out
     * 
     * @return bool
     */
    public function hasTimedOut(): bool
    {
        return $this->status === 'timed_out';
    }
}
