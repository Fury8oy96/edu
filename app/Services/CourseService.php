<?php

namespace App\Services;

use App\Models\Courses;
use App\Models\Students;
use App\Exceptions\AlreadyEnrolledException;
use App\Exceptions\UnverifiedStudentException;
use App\Exceptions\EnrollmentNotFoundException;
use App\Exceptions\NoActiveSubscriptionException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CourseService
{
    /**
     * List courses with optional filters and pagination
     *
     * @param array $filters Array of filters (search, instructor, category)
     * @param int $perPage Number of items per page
     * @return LengthAwarePaginator
     */
    public function listCourses(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Courses::query();

        // Apply search filter (title LIKE)
        if (isset($filters['search']) && !empty($filters['search'])) {
            $query->where('title', 'LIKE', '%' . $filters['search'] . '%');
        }

        // Apply instructor filter (instructor_id =)
        if (isset($filters['instructor']) && !empty($filters['instructor'])) {
            $query->where('instructor_id', $filters['instructor']);
        }

        // Apply category filter (category =)
        if (isset($filters['category']) && !empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Apply eager loading for instructor relationship to prevent N+1 queries
        $query->with('instructor');

        // Return paginated results
        return $query->paginate($perPage);
    }

    /**
     * Get course details with all relationships
     *
     * @param Courses $course
     * @return Courses
     */
    public function getCourseDetails(Courses $course): Courses
    {
        // Eager load instructor, modules with lessons
        // Order modules by 'id' field (no 'order' field available)
        // Order lessons within modules by 'id' field (no 'order' field available)
        $course->load([
            'instructor',
            'modules' => function ($query) {
                $query->orderBy('id');
            },
            'modules.lessons' => function ($query) {
                $query->orderBy('id');
            }
        ]);

        return $course;
    }

    /**
     * Enroll a student in a course
     *
     * @param Courses $course
     * @param Students $student
     * @return array Enrollment data
     * @throws UnverifiedStudentException
     * @throws AlreadyEnrolledException
     */
    public function enrollStudent(Courses $course, Students $student): array
    {
        // Check if student is verified
        if (!$student->isVerified()) {
            throw new UnverifiedStudentException();
        }

        // Check if course is paid and student has active subscription
        if ($course->is_paid && !$student->hasActiveSubscription()) {
            throw new NoActiveSubscriptionException();
        }

        // Check if already enrolled
        if ($student->courses()->where('course_id', $course->id)->exists()) {
            throw new AlreadyEnrolledException();
        }

        // Create enrollment
        $student->courses()->attach($course->id, [
            'enrolled_at' => now(),
            'status' => 'active',
            'progress_percentage' => 0
        ]);

        // Return enrollment data
        return [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'enrolled_at' => now()->toISOString(),
            'status' => 'active',
            'progress_percentage' => 0
        ];
    }

    /**
     * Get all courses a student is enrolled in with enrollment metadata
     *
     * @param Students $student
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStudentEnrollments(Students $student): \Illuminate\Database\Eloquent\Collection
    {
        // Query student's courses with instructor relationship
        // This will include pivot data (enrolled_at, status, progress_percentage)
        return $student->courses()->with('instructor')->get();
    }

    /**
     * Unenroll a student from a course
     *
     * @param Courses $course
     * @param Students $student
     * @return bool Success status
     * @throws EnrollmentNotFoundException
     */
    public function unenrollStudent(Courses $course, Students $student): bool
    {
        // Check if enrollment exists
        if (!$student->courses()->where('course_id', $course->id)->exists()) {
            throw new EnrollmentNotFoundException();
        }

        // Remove enrollment
        $student->courses()->detach($course->id);

        // Return success
        return true;
    }
}
