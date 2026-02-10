<?php

namespace App\Services;

use App\Models\AssessmentAttempt;

class GradeCalculator
{
    /**
     * Calculate grade for a student's course completion
     * 
     * @param int $studentId
     * @param int $courseId
     * @return array ['grade' => string|null, 'average_score' => float|null, 'scores' => array]
     */
    public function calculateGrade(int $studentId, int $courseId): array
    {
        $scores = $this->getAssessmentScores($studentId, $courseId);
        
        // Edge case: no assessments exist
        if (empty($scores)) {
            return [
                'grade' => 'Completed',
                'average_score' => null,
                'scores' => [],
            ];
        }
        
        // Calculate average score
        $average = array_sum($scores) / count($scores);
        
        // Round to 2 decimal places (Requirement 8.4)
        $average = round($average, 2);
        
        // Edge case: score below 60% returns null (no certificate)
        if ($average < 60) {
            return [
                'grade' => null,
                'average_score' => $average,
                'scores' => $scores,
            ];
        }
        
        // Map score to grade
        $grade = $this->mapScoreToGrade($average);
        
        return [
            'grade' => $grade,
            'average_score' => $average,
            'scores' => $scores,
        ];
    }
    
    /**
     * Map numeric score to grade classification
     * 
     * @param float $score
     * @return string
     */
    public function mapScoreToGrade(float $score): string
    {
        // Requirements 1.4-1.7, 8.5
        if ($score >= 90) {
            return 'Excellent';
        } elseif ($score >= 80) {
            return 'Very Good';
        } elseif ($score >= 70) {
            return 'Good';
        } else {
            return 'Pass';
        }
    }
    
    /**
     * Get all assessment scores for student in course
     * 
     * Retrieves only completed assessments with recorded scores (Requirement 8.3)
     * 
     * @param int $studentId
     * @param int $courseId
     * @return array Array of percentage scores
     */
    public function getAssessmentScores(int $studentId, int $courseId): array
    {
        // Get all completed assessment attempts for the student in the course
        // Only include attempts with status 'completed' and a recorded percentage
        $attempts = AssessmentAttempt::whereHas('assessment', function ($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })
        ->where('student_id', $studentId)
        ->where('status', 'completed')
        ->whereNotNull('percentage')
        ->get();
        
        // Extract percentage scores
        $scores = $attempts->pluck('percentage')->map(function ($score) {
            return (float) $score;
        })->toArray();
        
        return $scores;
    }
}
