<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'question_type',
        'question_text',
        'points',
        'order',
        'options',
        'correct_answer',
        'grading_rubric',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'order' => 'integer',
        'options' => 'array',
        'correct_answer' => 'json',
    ];

    /**
     * Get the assessment that this question belongs to
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Get all answers for this question
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class, 'question_id');
    }

    /**
     * Validate question type enum
     * 
     * @param string $type
     * @return bool
     */
    public static function isValidQuestionType(string $type): bool
    {
        return in_array($type, ['multiple_choice', 'true_false', 'short_answer', 'essay']);
    }
}
