<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question_text',
        'question_type',
        'points',
        'order',
        'options',
        'correct_answer',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'boolean',
    ];

    /**
     * Get the quiz that this question belongs to
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get all answers for this question
     */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class);
    }

    /**
     * Check if this question can be auto-graded
     */
    public function isAutoGradable(): bool
    {
        return in_array($this->question_type, ['multiple_choice', 'true_false']);
    }

    /**
     * Get the correct answer for this question
     */
    public function getCorrectAnswer(): mixed
    {
        if ($this->question_type === 'true_false') {
            return $this->correct_answer;
        }

        if ($this->question_type === 'multiple_choice' && is_array($this->options)) {
            foreach ($this->options as $option) {
                if (isset($option['is_correct']) && $option['is_correct']) {
                    return $option['text'] ?? null;
                }
            }
        }

        return null;
    }
}
