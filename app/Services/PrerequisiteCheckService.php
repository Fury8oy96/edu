<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentPrerequisite;
use App\Models\Courses;
use App\Models\Lessons;
use App\Models\QuizAttempt;
use App\Models\Students;
use Illuminate\Support\Facades\DB;

class PrerequisiteCheckService
{
    /**
     * Check if student meets all prerequisites for an assessment
     * 
     * @param int $assessmentId
     * @param int $studentId
     * @return array ['met' => bool, 'unmet' => array]
     */
    public function checkPrerequisites(int $assessmentId, int $studentId): array
    {
        $assessment = Assessment::with('prerequisites')->findOrFail($assessmentId);
        $student = Students::findOrFail($studentId);
        
        // If no prerequisites, all are met
        if ($assessment->prerequisites->isEmpty()) {
            return ['met' => true, 'unmet' => []];
        }
        
        $unmetPrerequisites = [];
        
        foreach ($assessment->prerequisites as $prerequisite) {
            $met = false;
            
            switch ($prerequisite->prerequisite_type) {
                case 'quiz_completion':
                    $met = $this->checkQuizCompletion($assessment->course_id, $studentId);
                    if (!$met) {
                        $unmetPrerequisites[] = [
                            'type' => 'quiz_completion',
                            'message' => 'All course quizzes must be completed and passed'
                        ];
                    }
                    break;
                    
                case 'minimum_progress':
                    $requiredPercentage = $prerequisite->prerequisite_data['minimum_percentage'] ?? 0;
                    $met = $this->checkMinimumProgress($assessment->course_id, $studentId, $requiredPercentage);
                    if (!$met) {
                        $unmetPrerequisites[] = [
                            'type' => 'minimum_progress',
                            'message' => "Course progress must be at least {$requiredPercentage}%",
                            'required_percentage' => $requiredPercentage
                        ];
                    }
                    break;
                    
                case 'lesson_completion':
                    $lessonIds = $prerequisite->prerequisite_data['lesson_ids'] ?? [];
                    $met = $this->checkLessonCompletion($lessonIds, $studentId);
                    if (!$met) {
                        $unmetPrerequisites[] = [
                            'type' => 'lesson_completion',
                            'message' => 'Required lessons must be completed',
                            'lesson_ids' => $lessonIds
                        ];
                    }
                    break;
            }
        }
        
        return [
            'met' => empty($unmetPrerequisites),
            'unmet' => $unmetPrerequisites
        ];
    }
    
    /**
     * Check if student has completed and passed all quizzes in a course
     * 
     * @param int $courseId
     * @param int $studentId
     * @return bool
     */
    public function checkQuizCompletion(int $courseId, int $studentId): bool
    {
        $course = Courses::with('modules.lessons.quiz')->findOrFail($courseId);
        
        // Get all quiz IDs in the course
        $quizIds = [];
        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                if ($lesson->quiz) {
                    $quizIds[] = $lesson->quiz->id;
                }
            }
        }
        
        // If no quizzes, requirement is met
        if (empty($quizIds)) {
            return true;
        }
        
        // Check if student has passed all quizzes
        foreach ($quizIds as $quizId) {
            $hasPassed = QuizAttempt::where('quiz_id', $quizId)
                ->where('student_id', $studentId)
                ->where('passed', true)
                ->exists();
                
            if (!$hasPassed) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if student has achieved minimum progress percentage in a course
     * 
     * @param int $courseId
     * @param int $studentId
     * @param float $requiredPercentage
     * @return bool
     */
    public function checkMinimumProgress(int $courseId, int $studentId, float $requiredPercentage): bool
    {
        $enrollment = DB::table('course_student')
            ->where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->first();
            
        if (!$enrollment) {
            return false;
        }
        
        return $enrollment->progress_percentage >= $requiredPercentage;
    }
    
    /**
     * Check if student has completed specific lessons
     * 
     * @param array $lessonIds
     * @param int $studentId
     * @return bool
     */
    public function checkLessonCompletion(array $lessonIds, int $studentId): bool
    {
        // For now, we'll check if the student has passed the quiz for each lesson
        // This is a simplified implementation - a full implementation would track lesson completion separately
        
        if (empty($lessonIds)) {
            return true;
        }
        
        foreach ($lessonIds as $lessonId) {
            $lesson = Lessons::with('quiz')->find($lessonId);
            
            if (!$lesson) {
                return false;
            }
            
            // If lesson has a quiz, check if student passed it
            if ($lesson->quiz) {
                $hasPassed = QuizAttempt::where('quiz_id', $lesson->quiz->id)
                    ->where('student_id', $studentId)
                    ->where('passed', true)
                    ->exists();
                    
                if (!$hasPassed) {
                    return false;
                }
            }
        }
        
        return true;
    }
}
