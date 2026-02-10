<?php

namespace App\Services;

use App\Exceptions\Assessment\AssessmentNotFoundException;
use App\Exceptions\Assessment\QuestionNotFoundException;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentPrerequisite;
use App\Models\Courses;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    /**
     * Create a new assessment with questions
     * 
     * @param array $data
     * @return Assessment
     * @throws ValidationException
     */
    public function createAssessment(array $data): Assessment
    {
        return DB::transaction(function () use ($data) {
            // Validate course exists
            $course = Courses::find($data['course_id']);
            if (!$course) {
                throw ValidationException::withMessages([
                    'course_id' => ['The selected course does not exist']
                ]);
            }
            
            // Extract questions if provided
            $questions = $data['questions'] ?? [];
            unset($data['questions']);
            
            // Create assessment
            $assessment = Assessment::create($data);
            
            // Create questions if provided
            if (!empty($questions)) {
                foreach ($questions as $index => $questionData) {
                    $questionData['assessment_id'] = $assessment->id;
                    $questionData['order'] = $questionData['order'] ?? ($index + 1);
                    $this->addQuestion($assessment->id, $questionData);
                }
            }
            
            return $assessment->load('questions');
        });
    }
    
    /**
     * Update an existing assessment
     * 
     * @param int $assessmentId
     * @param array $data
     * @return Assessment
     * @throws AssessmentNotFoundException
     * @throws ValidationException
     */
    public function updateAssessment(int $assessmentId, array $data): Assessment
    {
        $assessment = Assessment::find($assessmentId);
        if (!$assessment) {
            throw new AssessmentNotFoundException('Assessment not found');
        }
        
        // Remove questions from update data if present
        unset($data['questions']);
        
        $assessment->update($data);
        
        return $assessment->fresh();
    }
    
    /**
     * Delete an assessment and all related data
     * 
     * @param int $assessmentId
     * @return bool
     * @throws AssessmentNotFoundException
     */
    public function deleteAssessment(int $assessmentId): bool
    {
        $assessment = Assessment::find($assessmentId);
        if (!$assessment) {
            throw new AssessmentNotFoundException('Assessment not found');
        }
        
        // Delete assessment (cascade deletes questions, attempts, answers, prerequisites via foreign keys)
        return $assessment->delete();
    }
    
    /**
     * Add a question to an assessment
     * 
     * @param int $assessmentId
     * @param array $questionData
     * @return AssessmentQuestion
     * @throws AssessmentNotFoundException
     * @throws ValidationException
     */
    public function addQuestion(int $assessmentId, array $questionData): AssessmentQuestion
    {
        $assessment = Assessment::find($assessmentId);
        if (!$assessment) {
            throw new AssessmentNotFoundException('Assessment not found');
        }
        
        // Validate question type-specific requirements
        $this->validateQuestionData($questionData);
        
        // Add assessment_id to data
        $questionData['assessment_id'] = $assessmentId;
        
        // Set order if not provided
        if (!isset($questionData['order'])) {
            $maxOrder = $assessment->questions()->max('order') ?? 0;
            $questionData['order'] = $maxOrder + 1;
        }
        
        // Create question
        $question = AssessmentQuestion::create($questionData);
        
        return $question;
    }
    
    /**
     * Update a question
     * 
     * @param int $questionId
     * @param array $data
     * @return AssessmentQuestion
     * @throws QuestionNotFoundException
     * @throws ValidationException
     */
    public function updateQuestion(int $questionId, array $data): AssessmentQuestion
    {
        $question = AssessmentQuestion::find($questionId);
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
     * @return bool
     * @throws QuestionNotFoundException
     */
    public function deleteQuestion(int $questionId): bool
    {
        $question = AssessmentQuestion::find($questionId);
        if (!$question) {
            throw new QuestionNotFoundException('Question not found');
        }
        
        return $question->delete();
    }
    
    /**
     * Reorder questions in an assessment
     * 
     * @param int $assessmentId
     * @param array $questionOrders Array of ['question_id' => order]
     * @return void
     * @throws AssessmentNotFoundException
     */
    public function reorderQuestions(int $assessmentId, array $questionOrders): void
    {
        $assessment = Assessment::find($assessmentId);
        if (!$assessment) {
            throw new AssessmentNotFoundException('Assessment not found');
        }
        
        DB::transaction(function () use ($questionOrders) {
            foreach ($questionOrders as $questionId => $order) {
                AssessmentQuestion::where('id', $questionId)->update(['order' => $order]);
            }
        });
    }
    
    /**
     * Add prerequisite to assessment
     * 
     * @param int $assessmentId
     * @param array $prerequisiteData
     * @return AssessmentPrerequisite
     * @throws AssessmentNotFoundException
     * @throws ValidationException
     */
    public function addPrerequisite(int $assessmentId, array $prerequisiteData): AssessmentPrerequisite
    {
        $assessment = Assessment::find($assessmentId);
        if (!$assessment) {
            throw new AssessmentNotFoundException('Assessment not found');
        }
        
        // Validate prerequisite type
        if (!AssessmentPrerequisite::isValidPrerequisiteType($prerequisiteData['prerequisite_type'])) {
            throw ValidationException::withMessages([
                'prerequisite_type' => ['Invalid prerequisite type']
            ]);
        }
        
        // Validate prerequisite data based on type
        $this->validatePrerequisiteData($prerequisiteData);
        
        $prerequisiteData['assessment_id'] = $assessmentId;
        
        return AssessmentPrerequisite::create($prerequisiteData);
    }
    
    /**
     * Remove prerequisite
     * 
     * @param int $prerequisiteId
     * @return bool
     */
    public function removePrerequisite(int $prerequisiteId): bool
    {
        $prerequisite = AssessmentPrerequisite::find($prerequisiteId);
        if (!$prerequisite) {
            return false;
        }
        
        return $prerequisite->delete();
    }
    
    /**
     * Get assessment analytics
     * 
     * @param int $assessmentId
     * @param array|null $filters
     * @return array
     * @throws AssessmentNotFoundException
     */
    public function getAnalytics(int $assessmentId, ?array $filters = null): array
    {
        $assessment = Assessment::with(['attempts', 'questions'])->find($assessmentId);
        if (!$assessment) {
            throw new AssessmentNotFoundException('Assessment not found');
        }
        
        $query = $assessment->attempts()->whereIn('status', ['completed', 'timed_out', 'grading_pending']);
        
        // Apply date range filter if provided
        if (isset($filters['start_date'])) {
            $query->where('completion_time', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('completion_time', '<=', $filters['end_date']);
        }
        
        $attempts = $query->get();
        
        // Calculate assessment-level statistics
        $totalAttempts = $attempts->count();
        $completedAttempts = $attempts->where('passed', '!==', null);
        $averageScore = $completedAttempts->avg('percentage') ?? 0;
        $passedCount = $completedAttempts->where('passed', true)->count();
        $passRate = $completedAttempts->count() > 0 
            ? ($passedCount / $completedAttempts->count()) * 100 
            : 0;
        
        // Calculate question-level statistics
        $questionStats = [];
        foreach ($assessment->questions as $question) {
            $answers = DB::table('assessment_answers')
                ->whereIn('attempt_id', $attempts->pluck('id'))
                ->where('question_id', $question->id)
                ->get();
            
            $avgScore = $answers->avg('points_earned') ?? 0;
            $totalAnswers = $answers->count();
            
            // Get most common incorrect answers for multiple choice
            $incorrectAnswers = [];
            if ($question->question_type === 'multiple_choice') {
                $incorrectAnswers = $answers
                    ->where('is_correct', false)
                    ->groupBy('answer')
                    ->map(function ($group) {
                        return $group->count();
                    })
                    ->sortDesc()
                    ->take(3)
                    ->toArray();
            }
            
            $questionStats[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'average_score' => round($avgScore, 2),
                'max_points' => $question->points,
                'total_answers' => $totalAnswers,
                'most_common_incorrect_answers' => $incorrectAnswers,
            ];
        }
        
        return [
            'assessment_id' => $assessmentId,
            'total_attempts' => $totalAttempts,
            'average_score' => round($averageScore, 2),
            'pass_rate' => round($passRate, 2),
            'question_statistics' => $questionStats,
        ];
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
        
        // Validate question type
        if (!AssessmentQuestion::isValidQuestionType($questionType)) {
            $errors['question_type'] = ['Invalid question type'];
        }
        
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
    
    /**
     * Validate prerequisite data based on type
     * 
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    protected function validatePrerequisiteData(array $data): void
    {
        $errors = [];
        
        $type = $data['prerequisite_type'];
        $prerequisiteData = $data['prerequisite_data'] ?? [];
        
        if ($type === 'minimum_progress') {
            if (!isset($prerequisiteData['minimum_percentage'])) {
                $errors['prerequisite_data'] = ['minimum_percentage is required for minimum_progress type'];
            } elseif ($prerequisiteData['minimum_percentage'] < 0 || $prerequisiteData['minimum_percentage'] > 100) {
                $errors['prerequisite_data'] = ['minimum_percentage must be between 0 and 100'];
            }
        }
        
        if ($type === 'lesson_completion') {
            if (!isset($prerequisiteData['lesson_ids']) || !is_array($prerequisiteData['lesson_ids'])) {
                $errors['prerequisite_data'] = ['lesson_ids array is required for lesson_completion type'];
            }
        }
        
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
