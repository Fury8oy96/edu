<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AssessmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AssessmentAnalyticsController extends Controller
{
    protected AssessmentService $assessmentService;

    public function __construct(AssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    /**
     * Display analytics for an assessment
     * 
     * GET /admin/assessments/{assessmentId}/analytics
     */
    public function show(Request $request, int $assessmentId): View
    {
        $filters = $request->only(['start_date', 'end_date', 'student_id']);
        
        $analytics = $this->assessmentService->getAnalytics($assessmentId, $filters);

        return view('admin.assessment-analytics.show', compact('analytics', 'assessmentId'));
    }

    /**
     * Export analytics to CSV
     * 
     * GET /admin/assessments/{assessmentId}/analytics/export
     */
    public function export(Request $request, int $assessmentId): Response
    {
        $filters = $request->only(['start_date', 'end_date']);
        
        $analytics = $this->assessmentService->getAnalytics($assessmentId, $filters);
        
        // Generate CSV content
        $csv = $this->generateCsv($analytics);
        
        $filename = 'assessment_' . $assessmentId . '_analytics_' . date('Y-m-d') . '.csv';
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate CSV content from analytics data
     * 
     * @param array $analytics
     * @return string
     */
    protected function generateCsv(array $analytics): string
    {
        $output = fopen('php://temp', 'r+');
        
        // Write header
        fputcsv($output, ['Assessment Analytics Report']);
        fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // Overall statistics
        fputcsv($output, ['Overall Statistics']);
        fputcsv($output, ['Total Attempts', $analytics['total_attempts'] ?? 0]);
        fputcsv($output, ['Average Score', number_format($analytics['average_score'] ?? 0, 2)]);
        fputcsv($output, ['Pass Rate', number_format($analytics['pass_rate'] ?? 0, 2) . '%']);
        fputcsv($output, []);
        
        // Question statistics
        if (!empty($analytics['question_statistics'])) {
            fputcsv($output, ['Question Statistics']);
            fputcsv($output, ['Question ID', 'Question Text', 'Average Score', 'Question Type']);
            
            foreach ($analytics['question_statistics'] as $stat) {
                fputcsv($output, [
                    $stat['question_id'] ?? '',
                    substr($stat['question_text'] ?? '', 0, 100),
                    number_format($stat['average_score'] ?? 0, 2),
                    $stat['question_type'] ?? '',
                ]);
            }
            fputcsv($output, []);
        }
        
        // Student attempts (if filtered by student)
        if (!empty($analytics['student_attempts'])) {
            fputcsv($output, ['Student Attempts']);
            fputcsv($output, ['Attempt Number', 'Date', 'Score', 'Percentage', 'Passed', 'Time Taken (minutes)']);
            
            foreach ($analytics['student_attempts'] as $attempt) {
                fputcsv($output, [
                    $attempt['attempt_number'] ?? '',
                    $attempt['completion_time'] ?? '',
                    number_format($attempt['score'] ?? 0, 2),
                    number_format($attempt['percentage'] ?? 0, 2) . '%',
                    ($attempt['passed'] ?? false) ? 'Yes' : 'No',
                    isset($attempt['time_taken']) ? round($attempt['time_taken'] / 60, 2) : '',
                ]);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}
