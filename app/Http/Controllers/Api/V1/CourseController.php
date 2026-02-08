<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseListRequest;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseDetailResource;
use App\Models\Courses;
use App\Services\CourseService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    /**
     * Create a new CourseController instance
     * 
     * @param CourseService $courseService
     */
    public function __construct(
        private CourseService $courseService
    ) {}
    
    /**
     * List courses with optional filters and pagination
     * 
     * GET /api/v1/courses
     * 
     * @param CourseListRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(CourseListRequest $request): AnonymousResourceCollection
    {
        // Get validated filters
        $filters = $request->validated();
        
        // Get per_page parameter (default to 15)
        $perPage = $request->input('per_page', 15);
        
        // Call service to get paginated courses
        $courses = $this->courseService->listCourses($filters, $perPage);
        
        // Return resource collection
        return CourseResource::collection($courses);
    }
    
    /**
     * Show detailed course information
     * 
     * GET /api/v1/courses/{course}
     * 
     * @param Courses $course
     * @return CourseDetailResource
     */
    public function show(Courses $course): CourseDetailResource
    {
        // Load course details with relationships
        $course = $this->courseService->getCourseDetails($course);
        
        // Return detailed resource
        return new CourseDetailResource($course);
    }
    
    /**
     * Enroll authenticated student in a course
     * 
     * POST /api/v1/courses/{course}/enroll
     * 
     * @param Courses $course
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enroll(Courses $course, \Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Get authenticated student
            $student = $request->user();
            
            // Call service to enroll student
            $enrollmentData = $this->courseService->enrollStudent($course, $student);
            
            // Return JSON response with 201 status and enrollment data
            return response()->json([
                'message' => 'Successfully enrolled in course',
                'data' => $enrollmentData
            ], 201);
            
        } catch (\App\Exceptions\UnverifiedStudentException $e) {
            // Handle unverified student exception (403)
            return $e->render($request);
            
        } catch (\App\Exceptions\AlreadyEnrolledException $e) {
            // Handle already enrolled exception (409)
            return $e->render($request);
        }
    }
    
    /**
     * Get all courses the authenticated student is enrolled in
     * 
     * GET /api/v1/student/courses
     * 
     * @param \Illuminate\Http\Request $request
     * @return AnonymousResourceCollection
     */
    public function myEnrollments(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {
        // Get authenticated student
        $student = $request->user();
        
        // Call service to get student's enrolled courses
        $courses = $this->courseService->getStudentEnrollments($student);
        
        // Return enrolled course resource collection
        return \App\Http\Resources\EnrolledCourseResource::collection($courses);
    }
    
    /**
     * Unenroll authenticated student from a course
     * 
     * DELETE /api/v1/courses/{course}/unenroll
     * 
     * @param Courses $course
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unenroll(Courses $course, \Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Get authenticated student
            $student = $request->user();
            
            // Call service to unenroll student
            $this->courseService->unenrollStudent($course, $student);
            
            // Return JSON response with 200 status and success message
            return response()->json([
                'message' => 'Successfully unenrolled from course'
            ], 200);
            
        } catch (\App\Exceptions\EnrollmentNotFoundException $e) {
            // Handle enrollment not found exception (404)
            return $e->render($request);
        }
    }
}
