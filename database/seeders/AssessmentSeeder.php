<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentAnswer;
use App\Models\Courses;
use App\Models\Students;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing courses and students
        $courses = Courses::limit(3)->get();
        $students = Students::where('email_verified_at', '!=', null)->limit(5)->get();

        if ($courses->isEmpty() || $students->isEmpty()) {
            $this->command->warn('No courses or verified students found. Skipping assessment seeder.');
            return;
        }

        foreach ($courses as $course) {
            // Create 2 assessments per course
            for ($i = 1; $i <= 2; $i++) {
                $assessment = Assessment::factory()
                    ->for($course, 'course')
                    ->create([
                        'title' => "Assessment $i for {$course->name}",
                        'description' => "This is assessment $i for the {$course->name} course.",
                    ]);

                // Add mixed question types
                $questions = [];
                
                // 3 multiple choice questions
                for ($j = 1; $j <= 3; $j++) {
                    $questions[] = AssessmentQuestion::factory()
                        ->for($assessment)
                        ->multipleChoice()
                        ->create([
                            'order' => count($questions) + 1,
                            'question_text' => "Multiple choice question $j for {$assessment->title}?",
                        ]);
                }

                // 2 true/false questions
                for ($j = 1; $j <= 2; $j++) {
                    $questions[] = AssessmentQuestion::factory()
                        ->for($assessment)
                        ->trueFalse()
                        ->create([
                            'order' => count($questions) + 1,
                            'question_text' => "True/False question $j for {$assessment->title}?",
                        ]);
                }

                // 1 short answer question
                $questions[] = AssessmentQuestion::factory()
                    ->for($assessment)
                    ->shortAnswer()
                    ->create([
                        'order' => count($questions) + 1,
                        'question_text' => "Short answer question for {$assessment->title}?",
                    ]);

                // 1 essay question
                $questions[] = AssessmentQuestion::factory()
                    ->for($assessment)
                    ->essay()
                    ->create([
                        'order' => count($questions) + 1,
                        'question_text' => "Essay question for {$assessment->title}?",
                    ]);

                // Calculate max score
                $maxScore = collect($questions)->sum('points');

                // Create attempts for some students
                foreach ($students->take(3) as $index => $student) {
                    // Create 1-2 attempts per student
                    $attemptCount = rand(1, 2);
                    
                    for ($attemptNum = 1; $attemptNum <= $attemptCount; $attemptNum++) {
                        $attempt = AssessmentAttempt::factory()
                            ->for($assessment)
                            ->for($student, 'student')
                            ->create([
                                'attempt_number' => $attemptNum,
                                'max_score' => $maxScore,
                                'status' => $attemptNum === $attemptCount && rand(0, 1) ? 'grading_pending' : 'completed',
                            ]);

                        // Create answers for each question
                        foreach ($questions as $question) {
                            $answer = null;
                            
                            if ($question->question_type === 'multiple_choice') {
                                // Auto-graded - randomly correct or incorrect
                                $isCorrect = rand(0, 1) === 1;
                                $selectedOption = $isCorrect 
                                    ? $question->correct_answer['correct_option_id']
                                    : collect(['a', 'b', 'c', 'd'])->diff([$question->correct_answer['correct_option_id']])->random();
                                
                                $answer = AssessmentAnswer::factory()
                                    ->for($attempt)
                                    ->for($question, 'question')
                                    ->create([
                                        'answer' => ['selected_option_id' => $selectedOption],
                                        'is_correct' => $isCorrect,
                                        'points_earned' => $isCorrect ? $question->points : 0,
                                        'grading_status' => 'auto_graded',
                                    ]);
                            } elseif ($question->question_type === 'true_false') {
                                // Auto-graded - randomly correct or incorrect
                                $isCorrect = rand(0, 1) === 1;
                                $selectedValue = $isCorrect 
                                    ? $question->correct_answer['correct_value']
                                    : !$question->correct_answer['correct_value'];
                                
                                $answer = AssessmentAnswer::factory()
                                    ->for($attempt)
                                    ->for($question, 'question')
                                    ->create([
                                        'answer' => ['value' => $selectedValue],
                                        'is_correct' => $isCorrect,
                                        'points_earned' => $isCorrect ? $question->points : 0,
                                        'grading_status' => 'auto_graded',
                                    ]);
                            } elseif (in_array($question->question_type, ['short_answer', 'essay'])) {
                                // Manual grading - some graded, some pending
                                if ($attempt->status === 'grading_pending') {
                                    $answer = AssessmentAnswer::factory()
                                        ->for($attempt)
                                        ->for($question, 'question')
                                        ->pendingReview()
                                        ->create();
                                } else {
                                    $pointsEarned = $question->points * (rand(50, 100) / 100);
                                    $answer = AssessmentAnswer::factory()
                                        ->for($attempt)
                                        ->for($question, 'question')
                                        ->manuallyGraded($pointsEarned)
                                        ->create();
                                }
                            }
                        }

                        // Recalculate attempt score if completed
                        if ($attempt->status === 'completed') {
                            $totalScore = $attempt->answers->sum('points_earned');
                            $percentage = ($totalScore / $maxScore) * 100;
                            $passed = $percentage >= $assessment->passing_score;
                            
                            $attempt->update([
                                'score' => $totalScore,
                                'percentage' => $percentage,
                                'passed' => $passed,
                            ]);
                        }
                    }
                }

                $this->command->info("Created assessment: {$assessment->title} with " . count($questions) . " questions");
            }
        }

        $this->command->info('Assessment seeder completed successfully!');
    }
}
