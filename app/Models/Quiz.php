<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Quiz extends Model
{
    /** @use HasFactory<\Database\Factories\QuizFactory> */
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'title',
        'description',
        'time_limit_minutes',
        'passing_score_percentage',
        'max_attempts',
        'randomize_questions',
    ];

    protected $casts = [
        'randomize_questions' => 'boolean',
    ];

    /**
     * Get the lesson that this quiz belongs to
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lessons::class);
    }

    /**
     * Get all questions for this quiz
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Get all attempts for this quiz
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the total points for this quiz by summing all question points
     */
    public function getTotalPointsAttribute(): int
    {
        return $this->questions()->sum('points');
    }
}
