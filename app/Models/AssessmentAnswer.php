<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'answer',
        'is_correct',
        'points_earned',
        'grading_status',
        'grader_feedback',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'answer' => 'json',
        'is_correct' => 'boolean',
        'points_earned' => 'decimal:2',
        'graded_at' => 'datetime',
    ];

    /**
     * Get the attempt that this answer belongs to
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class);
    }

    /**
     * Get the question that this answer is for
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class);
    }

    /**
     * Get the user who graded this answer
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
