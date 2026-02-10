<?php

namespace App\Services;

use App\Exceptions\LessonNotFoundException;
use App\Exceptions\NotEnrolledException;
use App\Exceptions\QuestionNotFoundException;
use App\Exceptions\QuizNotFoundException;
use App\Models\Lessons;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Students;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    /**
     * Create a new quiz for a lesson
     * 
     * @param array $data
     * @return Quiz
     * @throws LessonNotFoundException
     * @throws ValidationException
     */
    public function createQuiz(array $data): Quiz
    {
        // Validate lesson exists
        $lesson = Lessons::find($data['lesson_id']);
        if (!$lesson) {
            throw new LessonNotFoundException('Lesson not found');
        }

        // Validate quiz parameters
        $this->validateQuizParameters($data);

        // Create quiz record
        $quiz = Quiz::create($data);

        return $quiz;
    }

    /**
     * Update an existing quiz
     * 
     * @param int $quizId
     * @param array $data
     * @return Quiz
     * @throws QuizNotFoundException
     * @throws ValidationException
     */
    public function updateQuiz(int $quizId, array $data): Quiz
    {
        $quiz = Quiz::find($quizId);
        if (!$quiz) {
            throw new QuizNotFoundException('Quiz not found');
        }

        // Validate quiz parameters if they are being updated
        if (isset($data['time_limit_minutes']) || isset($data['passing_score_percentage']) || isset($data['max_attempts'])) {
            $this->validateQuizParameters(array_merge($quiz->toArray(), $data));
        }

        $quiz->update($data);

        return $quiz->fresh();
    }

    /**
     * Delete a quiz and all associated data
     * 
     * @param int $quizId
     * @return void
     * @throws QuizNotFoundException
     */
    public function deleteQuiz(int $quizId): void
    {
        $quiz = Quiz::find($quizId);
        if (!$quiz) {
            throw new QuizNotFoundException('Quiz not found');
        }

        // Delete quiz (cascade deletes questions and attempts via foreign keys)
        $quiz->delete();
    }

    /**
     * Add a question to a quiz
     * 
     * @param int $quizId
     * @param array $data
     * @return Question
     * @throws QuizNotFoundException
     * @throws ValidationException
     */
    public function addQuestion(int $quizId, array $data): Question
    {
        $quiz = Quiz::find($quizId);
        if (!$quiz) {
            throw new QuizNotFoundException('Quiz not found');
        }

        // Validate question type-specific requirements
        $this->validateQuestionData($data);

        // Add quiz_id to data
        $data['quiz_id'] = $quizId;

        // Create question
        $question = Question::create($data);

        return $question;
    }

    /**
     * Update a question
     * 
     * @param int $questionId
     * @param array $data
     * @return Question
     * @throws QuestionNotFoundException
     * @throws ValidationException
     */
    public function updateQuestion(int $questionId, array $data): Question
    {
        $question = Question::find($questionId);
        if (!$question) {
            throw new QuestionNotFoundException('Question not found');
        }

        // Validate question data if type-specific fields are being updated
        if (isset($data['question_type']) || isset($data['options']) || isset($data['correct_answer'])) {
            $this->validateQuestionData(array_merge($question->toArray(), $data));
        }

        $question->update($data);

        return $question->fresh();
    }

    /**
     * Delete a question
     * 
     * @param int $questionId
     * @return void
     * @throws QuestionNotFoundException
     */
    public function deleteQuestion(int $questionId): void
    {
        $question = Question::find($questionId);
        if (!$question) {
            throw new QuestionNotFoundException('Question not found');
        }

        $question->delete();
    }

    /**
     * Get quiz details with questions for enrolled student
     * 
     * @param int $quizId
     * @param Students $student
     * @return Quiz
     * @throws QuizNotFoundException
     * @throws NotEnrolledException
     */
    public function getQuizForStudent(int $quizId, Students $student): Quiz
    {
        $quiz = Quiz::with(['questions' => function ($query) {
            $query->orderBy('order', 'asc');
        }, 'lesson.module.course'])->find($quizId);

        if (!$quiz) {
            throw new QuizNotFoundException('Quiz not found');
        }

        // Check student enrollment in course via lesson relationship
        $course = $quiz->lesson->module->course;
        $isEnrolled = $course->students()->where('student_id', $student->id)->exists();

        if (!$isEnrolled) {
            throw new NotEnrolledException('You must be enrolled in this course to access the quiz');
        }

        return $quiz;
    }

    /**
     * Validate quiz parameters
     * 
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    protected function validateQuizParameters(array $data): void
    {
        $errors = [];

        if (isset($data['time_limit_minutes'])) {
            if ($data['time_limit_minutes'] < 1 || $data['time_limit_minutes'] > 180) {
                $errors['time_limit_minutes'] = ['Time limit must be between 1 and 180 minutes'];
            }
        }

        if (isset($data['passing_score_percentage'])) {
            if ($data['passing_score_percentage'] < 0 || $data['passing_score_percentage'] > 100) {
                $errors['passing_score_percentage'] = ['Passing score must be between 0 and 100'];
            }
        }

        if (isset($data['max_attempts'])) {
            if ($data['max_attempts'] < 1 || $data['max_attempts'] > 10) {
                $errors['max_attempts'] = ['Max attempts must be between 1 and 10'];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate question data based on question type
     * 
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    protected function validateQuestionData(array $data): void
    {
        $errors = [];

        if (!isset($data['question_type'])) {
            return;
        }

        $questionType = $data['question_type'];

        // Validate multiple_choice questions
        if ($questionType === 'multiple_choice') {
            if (!isset($data['options']) || !is_array($data['options']) || count($data['options']) < 2) {
                $errors['options'] = ['Multiple choice questions must have at least 2 options'];
            } else {
                // Check that exactly one option is marked as correct
                $correctCount = 0;
                foreach ($data['options'] as $option) {
                    if (isset($option['is_correct']) && $option['is_correct']) {
                        $correctCount++;
                    }
                }

                if ($correctCount !== 1) {
                    $errors['options'] = ['Exactly one option must be marked as correct'];
                }
            }
        }

        // Validate true_false questions
        if ($questionType === 'true_false') {
            if (!isset($data['correct_answer']) || !is_bool($data['correct_answer'])) {
                $errors['correct_answer'] = ['True/false questions must have a boolean correct answer'];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
