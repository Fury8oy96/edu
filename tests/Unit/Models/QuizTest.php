<?php

use App\Models\Quiz;
use App\Models\Question;
use App\Models\Lessons;

beforeEach(function () {
    // Run migrations
    $this->artisan('migrate:fresh');
});

it('can create a quiz with all fillable fields', function () {
    $lesson = Lessons::factory()->create();
    
    $quiz = Quiz::create([
        'lesson_id' => $lesson->id,
        'title' => 'Test Quiz',
        'description' => 'This is a test quiz',
        'time_limit_minutes' => 60,
        'passing_score_percentage' => 70,
        'max_attempts' => 3,
        'randomize_questions' => true,
    ]);

    expect($quiz)->toBeInstanceOf(Quiz::class)
        ->and($quiz->lesson_id)->toBe($lesson->id)
        ->and($quiz->title)->toBe('Test Quiz')
        ->and($quiz->description)->toBe('This is a test quiz')
        ->and($quiz->time_limit_minutes)->toBe(60)
        ->and($quiz->passing_score_percentage)->toBe(70)
        ->and($quiz->max_attempts)->toBe(3)
        ->and($quiz->randomize_questions)->toBeTrue();
});

it('casts randomize_questions to boolean', function () {
    $lesson = Lessons::factory()->create();
    
    $quiz = Quiz::create([
        'lesson_id' => $lesson->id,
        'title' => 'Test Quiz',
        'time_limit_minutes' => 60,
        'passing_score_percentage' => 70,
        'max_attempts' => 3,
        'randomize_questions' => 1, // Integer
    ]);

    expect($quiz->randomize_questions)->toBeTrue()
        ->and($quiz->randomize_questions)->toBeBool();
});

it('has a lesson relationship', function () {
    $lesson = Lessons::factory()->create();
    
    $quiz = Quiz::create([
        'lesson_id' => $lesson->id,
        'title' => 'Test Quiz',
        'time_limit_minutes' => 60,
        'passing_score_percentage' => 70,
        'max_attempts' => 3,
    ]);

    expect($quiz->lesson)->toBeInstanceOf(Lessons::class)
        ->and($quiz->lesson->id)->toBe($lesson->id);
});

it('returns zero total points when quiz has no questions', function () {
    $lesson = Lessons::factory()->create();
    
    $quiz = Quiz::create([
        'lesson_id' => $lesson->id,
        'title' => 'Test Quiz',
        'time_limit_minutes' => 60,
        'passing_score_percentage' => 70,
        'max_attempts' => 3,
    ]);

    expect($quiz->total_points)->toBe(0);
});

it('calculates total points correctly when quiz has questions', function () {
    $lesson = Lessons::factory()->create();
    
    $quiz = Quiz::create([
        'lesson_id' => $lesson->id,
        'title' => 'Test Quiz',
        'time_limit_minutes' => 60,
        'passing_score_percentage' => 70,
        'max_attempts' => 3,
    ]);

    // Add questions with different point values
    Question::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Question 1',
        'question_type' => 'multiple_choice',
        'points' => 10,
        'order' => 1,
        'options' => [
            ['text' => 'Option A', 'is_correct' => true],
            ['text' => 'Option B', 'is_correct' => false],
        ],
    ]);

    Question::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Question 2',
        'question_type' => 'true_false',
        'points' => 5,
        'order' => 2,
        'correct_answer' => true,
    ]);

    Question::create([
        'quiz_id' => $quiz->id,
        'question_text' => 'Question 3',
        'question_type' => 'short_answer',
        'points' => 15,
        'order' => 3,
    ]);

    // Refresh to get updated relationships
    $quiz->refresh();

    expect($quiz->total_points)->toBe(30); // 10 + 5 + 15
});
