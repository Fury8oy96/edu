<?php

use App\Models\Courses;
use App\Models\Instructors;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->courseService = new CourseService();
});

test('listCourses returns paginated courses', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    Courses::factory()->count(5)->create(['instructor_id' => $instructor->id]);

    // Call the service
    $result = $this->courseService->listCourses([], 15);

    // Assert pagination structure
    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    expect($result->total())->toBe(5);
    expect($result->perPage())->toBe(15);
});

test('listCourses filters by search query', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    Courses::factory()->create(['title' => 'Laravel Basics', 'instructor_id' => $instructor->id]);
    Courses::factory()->create(['title' => 'PHP Advanced', 'instructor_id' => $instructor->id]);
    Courses::factory()->create(['title' => 'Laravel Advanced', 'instructor_id' => $instructor->id]);

    // Search for "Laravel"
    $result = $this->courseService->listCourses(['search' => 'Laravel'], 15);

    // Assert only Laravel courses are returned
    expect($result->total())->toBe(2);
    foreach ($result->items() as $course) {
        expect($course->title)->toContain('Laravel');
    }
});

test('listCourses filters by instructor', function () {
    // Create test data
    $instructor1 = Instructors::factory()->create();
    $instructor2 = Instructors::factory()->create();
    Courses::factory()->count(3)->create(['instructor_id' => $instructor1->id]);
    Courses::factory()->count(2)->create(['instructor_id' => $instructor2->id]);

    // Filter by instructor1
    $result = $this->courseService->listCourses(['instructor' => $instructor1->id], 15);

    // Assert only instructor1's courses are returned
    expect($result->total())->toBe(3);
    foreach ($result->items() as $course) {
        expect($course->instructor_id)->toBe($instructor1->id);
    }
});

test('listCourses filters by category', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    Courses::factory()->count(3)->create(['category' => 'Programming', 'instructor_id' => $instructor->id]);
    Courses::factory()->count(2)->create(['category' => 'Design', 'instructor_id' => $instructor->id]);

    // Filter by Programming category
    $result = $this->courseService->listCourses(['category' => 'Programming'], 15);

    // Assert only Programming courses are returned
    expect($result->total())->toBe(3);
    foreach ($result->items() as $course) {
        expect($course->category)->toBe('Programming');
    }
});

test('listCourses applies multiple filters', function () {
    // Create test data
    $instructor1 = Instructors::factory()->create();
    $instructor2 = Instructors::factory()->create();
    
    Courses::factory()->create([
        'title' => 'Laravel Basics',
        'category' => 'Programming',
        'instructor_id' => $instructor1->id
    ]);
    Courses::factory()->create([
        'title' => 'Laravel Advanced',
        'category' => 'Programming',
        'instructor_id' => $instructor1->id
    ]);
    Courses::factory()->create([
        'title' => 'PHP Basics',
        'category' => 'Programming',
        'instructor_id' => $instructor1->id
    ]);
    Courses::factory()->create([
        'title' => 'Laravel Design',
        'category' => 'Design',
        'instructor_id' => $instructor2->id
    ]);

    // Apply multiple filters
    $result = $this->courseService->listCourses([
        'search' => 'Laravel',
        'category' => 'Programming',
        'instructor' => $instructor1->id
    ], 15);

    // Assert only courses matching all filters are returned
    expect($result->total())->toBe(2);
    foreach ($result->items() as $course) {
        expect($course->title)->toContain('Laravel');
        expect($course->category)->toBe('Programming');
        expect($course->instructor_id)->toBe($instructor1->id);
    }
});

test('listCourses eager loads instructor relationship', function () {
    // Create test data
    $instructor = Instructors::factory()->create(['name' => 'John Doe']);
    Courses::factory()->create(['instructor_id' => $instructor->id]);

    // Call the service
    $result = $this->courseService->listCourses([], 15);

    // Assert instructor is loaded (no additional query needed)
    $course = $result->items()[0];
    expect($course->relationLoaded('instructor'))->toBeTrue();
    expect($course->instructor->name)->toBe('John Doe');
});

test('listCourses returns empty results when no courses match filters', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    Courses::factory()->create(['title' => 'Laravel Basics', 'instructor_id' => $instructor->id]);

    // Search for non-existent course
    $result = $this->courseService->listCourses(['search' => 'NonExistentCourse'], 15);

    // Assert empty results
    expect($result->total())->toBe(0);
    expect($result->items())->toBeEmpty();
});

test('listCourses respects perPage parameter', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    Courses::factory()->count(25)->create(['instructor_id' => $instructor->id]);

    // Request 10 items per page
    $result = $this->courseService->listCourses([], 10);

    // Assert pagination
    expect($result->perPage())->toBe(10);
    expect($result->count())->toBe(10);
    expect($result->total())->toBe(25);
    expect($result->lastPage())->toBe(3);
});

test('getCourseDetails loads course with all relationships', function () {
    // Create test data
    $instructor = Instructors::factory()->create(['name' => 'Jane Smith']);
    $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
    
    $module1 = \App\Models\Modules::factory()->create(['course_id' => $course->id]);
    $module2 = \App\Models\Modules::factory()->create(['course_id' => $course->id]);
    
    $lesson1 = \App\Models\Lessons::factory()->create(['module_id' => $module1->id]);
    $lesson2 = \App\Models\Lessons::factory()->create(['module_id' => $module1->id]);
    $lesson3 = \App\Models\Lessons::factory()->create(['module_id' => $module2->id]);

    // Call the service
    $result = $this->courseService->getCourseDetails($course);

    // Assert all relationships are loaded
    expect($result->relationLoaded('instructor'))->toBeTrue();
    expect($result->relationLoaded('modules'))->toBeTrue();
    expect($result->instructor->name)->toBe('Jane Smith');
    expect($result->modules)->toHaveCount(2);
    
    // Assert lessons are loaded within modules
    foreach ($result->modules as $module) {
        expect($module->relationLoaded('lessons'))->toBeTrue();
    }
});

test('getCourseDetails orders modules by id', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
    
    // Create modules in reverse order to test ordering
    $module3 = \App\Models\Modules::factory()->create(['course_id' => $course->id, 'title' => 'Module 3']);
    $module1 = \App\Models\Modules::factory()->create(['course_id' => $course->id, 'title' => 'Module 1']);
    $module2 = \App\Models\Modules::factory()->create(['course_id' => $course->id, 'title' => 'Module 2']);

    // Call the service
    $result = $this->courseService->getCourseDetails($course);

    // Assert modules are ordered by id (ascending)
    $moduleIds = $result->modules->pluck('id')->toArray();
    $sortedIds = collect($moduleIds)->sort()->values()->toArray();
    expect($moduleIds)->toBe($sortedIds);
});

test('getCourseDetails orders lessons within modules by id', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
    $module = \App\Models\Modules::factory()->create(['course_id' => $course->id]);
    
    // Create lessons in reverse order to test ordering
    $lesson3 = \App\Models\Lessons::factory()->create(['module_id' => $module->id, 'title' => 'Lesson 3']);
    $lesson1 = \App\Models\Lessons::factory()->create(['module_id' => $module->id, 'title' => 'Lesson 1']);
    $lesson2 = \App\Models\Lessons::factory()->create(['module_id' => $module->id, 'title' => 'Lesson 2']);

    // Call the service
    $result = $this->courseService->getCourseDetails($course);

    // Assert lessons are ordered by id (ascending) within the module
    $lessons = $result->modules->first()->lessons;
    $lessonIds = $lessons->pluck('id')->toArray();
    $sortedIds = collect($lessonIds)->sort()->values()->toArray();
    expect($lessonIds)->toBe($sortedIds);
});

test('getCourseDetails handles course with no modules', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
    // No modules created

    // Call the service
    $result = $this->courseService->getCourseDetails($course);

    // Assert relationships are loaded but empty
    expect($result->relationLoaded('instructor'))->toBeTrue();
    expect($result->relationLoaded('modules'))->toBeTrue();
    expect($result->modules)->toBeEmpty();
});

test('getCourseDetails handles module with no lessons', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
    $module = \App\Models\Modules::factory()->create(['course_id' => $course->id]);
    // No lessons created

    // Call the service
    $result = $this->courseService->getCourseDetails($course);

    // Assert module is loaded but has no lessons
    expect($result->modules)->toHaveCount(1);
    expect($result->modules->first()->relationLoaded('lessons'))->toBeTrue();
    expect($result->modules->first()->lessons)->toBeEmpty();
});

test('enrollStudent successfully enrolls a verified student', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now()
    ]);

    // Enroll the student
    $result = $this->courseService->enrollStudent($course, $student);

    // Assert enrollment was created
    expect($result)->toBeArray();
    expect($result['course_id'])->toBe($course->id);
    expect($result['student_id'])->toBe($student->id);
    expect($result['status'])->toBe('active');
    expect($result['progress_percentage'])->toBe(0);
    expect($result['enrolled_at'])->not->toBeNull();

    // Verify enrollment exists in database
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course->id,
        'student_id' => $student->id,
        'status' => 'active',
        'progress_percentage' => 0
    ]);
});

test('enrollStudent throws exception for unverified student', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => null // Unverified student
    ]);

    // Attempt to enroll unverified student
    $this->courseService->enrollStudent($course, $student);
})->throws(\App\Exceptions\UnverifiedStudentException::class);

test('enrollStudent throws exception for duplicate enrollment', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now()
    ]);

    // Enroll the student first time
    $this->courseService->enrollStudent($course, $student);

    // Attempt to enroll again
    $this->courseService->enrollStudent($course, $student);
})->throws(\App\Exceptions\AlreadyEnrolledException::class);

test('enrollStudent sets correct enrollment data', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now()
    ]);

    // Enroll the student
    $result = $this->courseService->enrollStudent($course, $student);

    // Verify the student can access the course through relationship
    $enrolledCourses = $student->courses()->get();
    expect($enrolledCourses)->toHaveCount(1);
    expect($enrolledCourses->first()->id)->toBe($course->id);
    
    // Verify pivot data
    $pivot = $enrolledCourses->first()->pivot;
    expect($pivot->status)->toBe('active');
    expect($pivot->progress_percentage)->toBe(0);
    expect($pivot->enrolled_at)->not->toBeNull();
});

test('enrollStudent allows same student to enroll in different courses', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course1 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $course2 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now()
    ]);

    // Enroll in first course
    $result1 = $this->courseService->enrollStudent($course1, $student);
    expect($result1['course_id'])->toBe($course1->id);

    // Enroll in second course
    $result2 = $this->courseService->enrollStudent($course2, $student);
    expect($result2['course_id'])->toBe($course2->id);

    // Verify both enrollments exist
    $enrolledCourses = $student->courses()->get();
    expect($enrolledCourses)->toHaveCount(2);
});

test('enrollStudent allows different students to enroll in same course', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student1 = \App\Models\Students::factory()->create(['email_verified_at' => now()]);
    $student2 = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll both students
    $result1 = $this->courseService->enrollStudent($course, $student1);
    $result2 = $this->courseService->enrollStudent($course, $student2);

    expect($result1['student_id'])->toBe($student1->id);
    expect($result2['student_id'])->toBe($student2->id);

    // Verify both enrollments exist
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course->id,
        'student_id' => $student1->id
    ]);
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course->id,
        'student_id' => $student2->id
    ]);
});

test('getStudentEnrollments returns all enrolled courses with instructor', function () {
    // Create test data
    $instructor1 = Instructors::factory()->create(['name' => 'John Doe']);
    $instructor2 = Instructors::factory()->create(['name' => 'Jane Smith']);
    $course1 = Courses::factory()->create([
        'instructor_id' => $instructor1->id,
        'title' => 'Course 1',
        'is_paid' => false  // Free course
    ]);
    $course2 = Courses::factory()->create([
        'instructor_id' => $instructor2->id,
        'title' => 'Course 2',
        'is_paid' => false  // Free course
    ]);
    $course3 = Courses::factory()->create([
        'instructor_id' => $instructor1->id,
        'title' => 'Course 3',
        'is_paid' => false  // Free course
    ]);
    
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll student in course1 and course2 (not course3)
    $this->courseService->enrollStudent($course1, $student);
    $this->courseService->enrollStudent($course2, $student);

    // Get student enrollments
    $result = $this->courseService->getStudentEnrollments($student);

    // Assert correct courses are returned
    expect($result)->toHaveCount(2);
    expect($result->pluck('id')->toArray())->toContain($course1->id, $course2->id);
    expect($result->pluck('id')->toArray())->not->toContain($course3->id);
    
    // Assert instructor relationship is loaded
    foreach ($result as $course) {
        expect($course->relationLoaded('instructor'))->toBeTrue();
        expect($course->instructor)->not->toBeNull();
    }
});

test('getStudentEnrollments includes enrollment metadata from pivot', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll student
    $this->courseService->enrollStudent($course, $student);

    // Get student enrollments
    $result = $this->courseService->getStudentEnrollments($student);

    // Assert pivot data is accessible
    expect($result)->toHaveCount(1);
    $enrolledCourse = $result->first();
    expect($enrolledCourse->pivot)->not->toBeNull();
    expect($enrolledCourse->pivot->enrolled_at)->not->toBeNull();
    expect($enrolledCourse->pivot->status)->toBe('active');
    expect($enrolledCourse->pivot->progress_percentage)->toBe(0);
});

test('getStudentEnrollments returns empty collection for student with no enrollments', function () {
    // Create test data
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Get student enrollments (should be empty)
    $result = $this->courseService->getStudentEnrollments($student);

    // Assert empty collection
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($result)->toBeEmpty();
});

test('getStudentEnrollments returns only courses for specific student', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course1 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $course2 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $course3 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    
    $student1 = \App\Models\Students::factory()->create(['email_verified_at' => now()]);
    $student2 = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll student1 in course1 and course2
    $this->courseService->enrollStudent($course1, $student1);
    $this->courseService->enrollStudent($course2, $student1);
    
    // Enroll student2 in course3
    $this->courseService->enrollStudent($course3, $student2);

    // Get enrollments for student1
    $result1 = $this->courseService->getStudentEnrollments($student1);
    
    // Get enrollments for student2
    $result2 = $this->courseService->getStudentEnrollments($student2);

    // Assert student1 only sees their courses
    expect($result1)->toHaveCount(2);
    expect($result1->pluck('id')->toArray())->toContain($course1->id, $course2->id);
    expect($result1->pluck('id')->toArray())->not->toContain($course3->id);
    
    // Assert student2 only sees their courses
    expect($result2)->toHaveCount(1);
    expect($result2->pluck('id')->toArray())->toContain($course3->id);
    expect($result2->pluck('id')->toArray())->not->toContain($course1->id, $course2->id);
});

test('getStudentEnrollments eager loads instructor to prevent N+1 queries', function () {
    // Create test data
    $instructor1 = Instructors::factory()->create();
    $instructor2 = Instructors::factory()->create();
    $course1 = Courses::factory()->create([
        'instructor_id' => $instructor1->id,
        'is_paid' => false  // Free course
    ]);
    $course2 = Courses::factory()->create([
        'instructor_id' => $instructor2->id,
        'is_paid' => false  // Free course
    ]);
    
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll student in both courses
    $this->courseService->enrollStudent($course1, $student);
    $this->courseService->enrollStudent($course2, $student);

    // Get student enrollments
    $result = $this->courseService->getStudentEnrollments($student);

    // Assert instructor is eager loaded (no additional queries needed)
    foreach ($result as $course) {
        expect($course->relationLoaded('instructor'))->toBeTrue();
        expect($course->instructor)->not->toBeNull();
    }
});

test('getStudentEnrollments returns courses with complete course details', function () {
    // Create test data
    $instructor = Instructors::factory()->create(['name' => 'Test Instructor']);
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Test Course',
        'description' => 'Test Description',
        'price' => 99.99,
        'duration_hours' => 10,
        'is_paid' => false // Make it a free course to avoid subscription check
    ]);
    
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll student
    $this->courseService->enrollStudent($course, $student);

    // Get student enrollments
    $result = $this->courseService->getStudentEnrollments($student);

    // Assert course details are complete
    expect($result)->toHaveCount(1);
    $enrolledCourse = $result->first();
    expect($enrolledCourse->title)->toBe('Test Course');
    expect($enrolledCourse->description)->toBe('Test Description');
    expect((float)$enrolledCourse->price)->toBe(99.99);
    expect($enrolledCourse->duration_hours)->toBe(10);
    expect($enrolledCourse->instructor->name)->toBe('Test Instructor');
});

test('unenrollStudent successfully removes enrollment', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll the student first
    $this->courseService->enrollStudent($course, $student);
    
    // Verify enrollment exists
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course->id,
        'student_id' => $student->id
    ]);

    // Unenroll the student
    $result = $this->courseService->unenrollStudent($course, $student);

    // Assert success
    expect($result)->toBeTrue();

    // Verify enrollment was removed from database
    $this->assertDatabaseMissing('course_student', [
        'course_id' => $course->id,
        'student_id' => $student->id
    ]);
});

test('unenrollStudent throws exception when student is not enrolled', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Attempt to unenroll without being enrolled
    $this->courseService->unenrollStudent($course, $student);
})->throws(\App\Exceptions\EnrollmentNotFoundException::class);

test('unenrollStudent only removes enrollment for specific course', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course1 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $course2 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll student in both courses
    $this->courseService->enrollStudent($course1, $student);
    $this->courseService->enrollStudent($course2, $student);

    // Unenroll from course1 only
    $result = $this->courseService->unenrollStudent($course1, $student);

    // Assert course1 enrollment removed
    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('course_student', [
        'course_id' => $course1->id,
        'student_id' => $student->id
    ]);

    // Assert course2 enrollment still exists
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course2->id,
        'student_id' => $student->id
    ]);
});

test('unenrollStudent only removes enrollment for specific student', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student1 = \App\Models\Students::factory()->create(['email_verified_at' => now()]);
    $student2 = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll both students in the same course
    $this->courseService->enrollStudent($course, $student1);
    $this->courseService->enrollStudent($course, $student2);

    // Unenroll student1 only
    $result = $this->courseService->unenrollStudent($course, $student1);

    // Assert student1 enrollment removed
    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('course_student', [
        'course_id' => $course->id,
        'student_id' => $student1->id
    ]);

    // Assert student2 enrollment still exists
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course->id,
        'student_id' => $student2->id
    ]);
});

test('unenrollStudent allows re-enrollment after unenrolling', function () {
    // Create test data
    $instructor = Instructors::factory()->create();
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false  // Free course
    ]);
    $student = \App\Models\Students::factory()->create(['email_verified_at' => now()]);

    // Enroll the student
    $this->courseService->enrollStudent($course, $student);

    // Unenroll the student
    $this->courseService->unenrollStudent($course, $student);

    // Re-enroll the student (should succeed)
    $result = $this->courseService->enrollStudent($course, $student);

    // Assert re-enrollment was successful
    expect($result)->toBeArray();
    expect($result['course_id'])->toBe($course->id);
    expect($result['student_id'])->toBe($student->id);
    
    // Verify enrollment exists in database
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course->id,
        'student_id' => $student->id
    ]);
});
