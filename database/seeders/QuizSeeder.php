<?php

namespace Database\Seeders;

use App\Models\Lessons;
use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some lessons to attach quizzes to
        $lessons = Lessons::take(5)->get();

        if ($lessons->isEmpty()) {
            $this->command->warn('No lessons found. Please seed lessons first.');
            return;
        }

        foreach ($lessons as $lesson) {
            // Create a quiz for each lesson
            $quiz = Quiz::create([
                'lesson_id' => $lesson->id,
                'title' => 'Quiz: ' . $lesson->title,
                'description' => 'Test your knowledge on ' . $lesson->title,
                'time_limit_minutes' => rand(15, 60),
                'passing_score_percentage' => rand(60, 80),
                'max_attempts' => rand(2, 5),
                'randomize_questions' => (bool) rand(0, 1),
            ]);

            // Add multiple choice questions
            for ($i = 1; $i <= 3; $i++) {
                Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => "Multiple choice question $i for {$lesson->title}?",
                    'question_type' => 'multiple_choice',
                    'points' => rand(5, 10),
                    'order' => $i,
                    'options' => [
                        ['text' => 'Option A', 'is_correct' => true],
                        ['text' => 'Option B', 'is_correct' => false],
                        ['text' => 'Option C', 'is_correct' => false],
                        ['text' => 'Option D', 'is_correct' => false],
                    ],
                ]);
            }

            // Add true/false questions
            for ($i = 4; $i <= 6; $i++) {
                Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => "True/False question " . ($i - 3) . " for {$lesson->title}?",
                    'question_type' => 'true_false',
                    'points' => rand(3, 5),
                    'order' => $i,
                    'correct_answer' => (bool) rand(0, 1),
                ]);
            }

            // Add short answer questions
            for ($i = 7; $i <= 8; $i++) {
                Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => "Short answer question " . ($i - 6) . " for {$lesson->title}?",
                    'question_type' => 'short_answer',
                    'points' => rand(10, 15),
                    'order' => $i,
                ]);
            }

            $this->command->info("Created quiz '{$quiz->title}' with 8 questions");
        }
    }
}
