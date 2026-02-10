<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\QuizAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'student_id',
        'started_at',
        'submitted_at',
        'deadline',
        'score',
        'score_percentage',
        'passed',
        'requires_grading',
        'time_taken_minutes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'deadline' => 'datetime',
        'passed' => 'boolean',
        'requires_grading' => 'boolean',
    ];

    /**
     * Get the quiz for this attempt
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
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
        return $this->hasMany(QuizAnswer::class);
    }

    /**
     * Check if this attempt has been submitted
     */
    public function isSubmitted(): bool
    {
        return !is_null($this->submitted_at);
    }

    /**
     * Check if this attempt has expired (deadline passed)
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->deadline);
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingTimeSeconds(): int
    {
        if ($this->isSubmitted()) {
            return 0;
        }

        $remaining = $this->deadline->diffInSeconds(now(), false);
        return max(0, (int) -$remaining);
    }
}
