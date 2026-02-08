<?php

use App\Models\Courses;
use App\Models\Instructors;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('index endpoint returns paginated courses with 200 status', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create some courses
    Courses::factory()->count(5)->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Make request to index endpoint
    $response = $this->getJson('/api/v1/courses');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response has pagination structure
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'title',
                'description',
                'price',
                'duration_hours',
                'level',
                'category',
                'instructor',
                'enrollment_count',
                'created_at',
            ],
        ],
        'links',
        'meta' => [
            'current_page',
            'from',
            'last_page',
            'per_page',
            'to',
            'total',
        ],
    ]);
    
    // Assert we have 5 courses in the response
    expect($response->json('data'))->toHaveCount(5);
});

test('index endpoint returns courses with instructor data', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create([
        'name' => 'John Instructor',
        'email' => 'john@instructor.com',
    ]);
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Test Course',
    ]);
    
    // Make request to index endpoint
    $response = $this->getJson('/api/v1/courses');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert instructor data is included
    $response->assertJsonFragment([
        'title' => 'Test Course',
    ]);
    
    // Get the first course from response
    $courseData = $response->json('data.0');
    
    // Assert instructor is loaded
    expect($courseData['instructor'])->not->toBeNull();
    expect($courseData['instructor']['name'])->toBe('John Instructor');
});

test('index endpoint filters courses by search query', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create courses with different titles
    Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Laravel Basics',
    ]);
    
    Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'PHP Advanced',
    ]);
    
    Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Laravel Advanced',
    ]);
    
    // Search for "Laravel"
    $response = $this->getJson('/api/v1/courses?search=Laravel');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert only 2 courses are returned
    expect($response->json('data'))->toHaveCount(2);
    
    // Assert all returned courses have "Laravel" in title
    $courses = $response->json('data');
    foreach ($courses as $course) {
        expect(stripos($course['title'], 'Laravel'))->not->toBeFalse();
    }
});

test('index endpoint filters courses by instructor', function () {
    // Create two instructors
    $instructor1 = Instructors::factory()->create(['name' => 'Instructor One']);
    $instructor2 = Instructors::factory()->create(['name' => 'Instructor Two']);
    
    // Create courses for each instructor
    Courses::factory()->count(3)->create(['instructor_id' => $instructor1->id]);
    Courses::factory()->count(2)->create(['instructor_id' => $instructor2->id]);
    
    // Filter by instructor1
    $response = $this->getJson('/api/v1/courses?instructor=' . $instructor1->id);
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert only 3 courses are returned
    expect($response->json('data'))->toHaveCount(3);
    
    // Assert all returned courses belong to instructor1
    $courses = $response->json('data');
    foreach ($courses as $course) {
        expect($course['instructor']['id'])->toBe($instructor1->id);
    }
});

test('index endpoint filters courses by category', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create courses with different categories
    Courses::factory()->count(2)->create([
        'instructor_id' => $instructor->id,
        'category' => 'Programming',
    ]);
    
    Courses::factory()->count(3)->create([
        'instructor_id' => $instructor->id,
        'category' => 'Design',
    ]);
    
    // Filter by "Programming" category
    $response = $this->getJson('/api/v1/courses?category=Programming');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert only 2 courses are returned
    expect($response->json('data'))->toHaveCount(2);
    
    // Assert all returned courses have "Programming" category
    $courses = $response->json('data');
    foreach ($courses as $course) {
        expect($course['category'])->toBe('Programming');
    }
});

test('index endpoint applies multiple filters simultaneously', function () {
    // Create two instructors
    $instructor1 = Instructors::factory()->create();
    $instructor2 = Instructors::factory()->create();
    
    // Create courses with various attributes
    Courses::factory()->create([
        'instructor_id' => $instructor1->id,
        'title' => 'Laravel Basics',
        'category' => 'Programming',
    ]);
    
    Courses::factory()->create([
        'instructor_id' => $instructor1->id,
        'title' => 'Laravel Advanced',
        'category' => 'Design',
    ]);
    
    Courses::factory()->create([
        'instructor_id' => $instructor2->id,
        'title' => 'Laravel Expert',
        'category' => 'Programming',
    ]);
    
    // Apply multiple filters: search + instructor + category
    $response = $this->getJson('/api/v1/courses?search=Laravel&instructor=' . $instructor1->id . '&category=Programming');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert only 1 course matches all criteria
    expect($response->json('data'))->toHaveCount(1);
    
    // Assert the returned course matches all filters
    $course = $response->json('data.0');
    expect($course['title'])->toContain('Laravel');
    expect($course['instructor']['id'])->toBe($instructor1->id);
    expect($course['category'])->toBe('Programming');
});

test('index endpoint returns empty array when no courses match filters', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create some courses
    Courses::factory()->count(3)->create([
        'instructor_id' => $instructor->id,
        'title' => 'PHP Course',
    ]);
    
    // Search for something that doesn't exist
    $response = $this->getJson('/api/v1/courses?search=NonExistentCourse');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert empty data array
    expect($response->json('data'))->toBeArray();
    expect($response->json('data'))->toHaveCount(0);
    
    // Assert pagination metadata is still present
    $response->assertJsonStructure([
        'data',
        'links',
        'meta',
    ]);
});

test('index endpoint respects per_page parameter', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create 20 courses
    Courses::factory()->count(20)->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Request with per_page=5
    $response = $this->getJson('/api/v1/courses?per_page=5');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert only 5 courses are returned
    expect($response->json('data'))->toHaveCount(5);
    
    // Assert pagination metadata shows correct per_page
    expect($response->json('meta.per_page'))->toBe(5);
    expect($response->json('meta.total'))->toBe(20);
});

test('index endpoint validates per_page maximum', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create some courses
    Courses::factory()->count(5)->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Request with per_page > 100 (should fail validation)
    $response = $this->getJson('/api/v1/courses?per_page=150');
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for per_page
    $response->assertJsonValidationErrors(['per_page']);
});

test('index endpoint validates instructor exists', function () {
    // Request with non-existent instructor ID
    $response = $this->getJson('/api/v1/courses?instructor=99999');
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for instructor
    $response->assertJsonValidationErrors(['instructor']);
});


test('show endpoint returns course details with 200 status', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create([
        'name' => 'John Instructor',
        'email' => 'john@instructor.com',
        'bio' => 'Experienced instructor',
    ]);
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Test Course',
        'description' => 'Test Description',
        'requirements' => 'Basic knowledge',
        'outcomes' => 'Learn advanced concepts',
        'target_audience' => 'Beginners',
    ]);
    
    // Make request to show endpoint
    $response = $this->getJson("/api/v1/courses/{$course->id}");
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response has correct structure
    $response->assertJsonStructure([
        'data' => [
            'id',
            'title',
            'description',
            'price',
            'duration_hours',
            'level',
            'category',
            'requirements',
            'outcomes',
            'target_audience',
            'instructor' => [
                'id',
                'name',
                'email',
                'bio',
            ],
            'modules',
            'enrollment_count',
            'created_at',
        ],
    ]);
    
    // Assert course data is correct
    $response->assertJsonFragment([
        'title' => 'Test Course',
        'description' => 'Test Description',
        'requirements' => 'Basic knowledge',
        'outcomes' => 'Learn advanced concepts',
        'target_audience' => 'Beginners',
    ]);
    
    // Assert instructor data is included
    $response->assertJsonFragment([
        'name' => 'John Instructor',
        'email' => 'john@instructor.com',
        'bio' => 'Experienced instructor',
    ]);
});

test('show endpoint returns course with modules and lessons', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Create modules for the course
    $module1 = \App\Models\Modules::factory()->create([
        'course_id' => $course->id,
        'title' => 'Module 1',
    ]);
    
    $module2 = \App\Models\Modules::factory()->create([
        'course_id' => $course->id,
        'title' => 'Module 2',
    ]);
    
    // Create lessons for modules
    \App\Models\Lessons::factory()->create([
        'module_id' => $module1->id,
        'title' => 'Lesson 1.1',
    ]);
    
    \App\Models\Lessons::factory()->create([
        'module_id' => $module1->id,
        'title' => 'Lesson 1.2',
    ]);
    
    \App\Models\Lessons::factory()->create([
        'module_id' => $module2->id,
        'title' => 'Lesson 2.1',
    ]);
    
    // Make request to show endpoint
    $response = $this->getJson("/api/v1/courses/{$course->id}");
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert modules are included
    $modules = $response->json('data.modules');
    expect($modules)->toHaveCount(2);
    
    // Assert lessons are included in modules
    expect($modules[0]['lessons'])->toHaveCount(2);
    expect($modules[1]['lessons'])->toHaveCount(1);
    
    // Assert module and lesson structure
    $response->assertJsonStructure([
        'data' => [
            'modules' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'duration',
                    'lessons' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'duration',
                            'video_url',
                        ],
                    ],
                ],
            ],
        ],
    ]);
});

test('show endpoint returns 404 for non-existent course', function () {
    // Make request with non-existent course ID
    $response = $this->getJson('/api/v1/courses/99999');
    
    // Assert response status is 404 Not Found
    $response->assertStatus(404);
});

test('show endpoint orders modules by id', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Create modules with specific IDs (create in reverse order to test ordering)
    $module3 = \App\Models\Modules::factory()->create([
        'course_id' => $course->id,
        'title' => 'Module 3',
    ]);
    
    $module1 = \App\Models\Modules::factory()->create([
        'course_id' => $course->id,
        'title' => 'Module 1',
    ]);
    
    $module2 = \App\Models\Modules::factory()->create([
        'course_id' => $course->id,
        'title' => 'Module 2',
    ]);
    
    // Make request to show endpoint
    $response = $this->getJson("/api/v1/courses/{$course->id}");
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Get modules from response
    $modules = $response->json('data.modules');
    
    // Assert modules are ordered by ID (ascending)
    expect($modules[0]['id'])->toBeLessThan($modules[1]['id']);
    expect($modules[1]['id'])->toBeLessThan($modules[2]['id']);
});

test('show endpoint orders lessons by id within modules', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Create a module
    $module = \App\Models\Modules::factory()->create([
        'course_id' => $course->id,
    ]);
    
    // Create lessons in reverse order to test ordering
    $lesson3 = \App\Models\Lessons::factory()->create([
        'module_id' => $module->id,
        'title' => 'Lesson 3',
    ]);
    
    $lesson1 = \App\Models\Lessons::factory()->create([
        'module_id' => $module->id,
        'title' => 'Lesson 1',
    ]);
    
    $lesson2 = \App\Models\Lessons::factory()->create([
        'module_id' => $module->id,
        'title' => 'Lesson 2',
    ]);
    
    // Make request to show endpoint
    $response = $this->getJson("/api/v1/courses/{$course->id}");
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Get lessons from response
    $lessons = $response->json('data.modules.0.lessons');
    
    // Assert lessons are ordered by ID (ascending)
    expect($lessons[0]['id'])->toBeLessThan($lessons[1]['id']);
    expect($lessons[1]['id'])->toBeLessThan($lessons[2]['id']);
});

test('enroll endpoint successfully enrolls verified student with 201 status', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a free course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false,  // Free course
    ]);
    
    // Create a verified student
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request to enroll endpoint
    $response = $this->postJson("/api/v1/courses/{$course->id}/enroll");
    
    // Assert response status is 201 Created
    $response->assertStatus(201);
    
    // Assert response structure
    $response->assertJsonStructure([
        'message',
        'data' => [
            'course_id',
            'student_id',
            'enrolled_at',
            'status',
            'progress_percentage',
        ],
    ]);
    
    // Assert enrollment data is correct
    $response->assertJsonFragment([
        'course_id' => $course->id,
        'student_id' => $student->id,
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
    
    // Assert enrollment exists in database
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course->id,
        'student_id' => $student->id,
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
});

test('enroll endpoint rejects unverified student with 403 status', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Create an unverified student (email_verified_at is null)
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => null,
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request to enroll endpoint
    $response = $this->postJson("/api/v1/courses/{$course->id}/enroll");
    
    // Assert response status is 403 Forbidden
    $response->assertStatus(403);
    
    // Assert error message
    $response->assertJsonFragment([
        'message' => 'Email verification required to enroll in courses',
    ]);
    
    // Assert enrollment does NOT exist in database
    $this->assertDatabaseMissing('course_student', [
        'course_id' => $course->id,
        'student_id' => $student->id,
    ]);
});

test('enroll endpoint rejects duplicate enrollment with 409 status', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a free course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'is_paid' => false,  // Free course
    ]);
    
    // Create a verified student
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Enroll the student in the course
    $student->courses()->attach($course->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request to enroll endpoint (second time)
    $response = $this->postJson("/api/v1/courses/{$course->id}/enroll");
    
    // Assert response status is 409 Conflict
    $response->assertStatus(409);
    
    // Assert error message
    $response->assertJsonFragment([
        'message' => 'You are already enrolled in this course',
    ]);
    
    // Assert only one enrollment exists in database
    $enrollmentCount = \Illuminate\Support\Facades\DB::table('course_student')
        ->where('course_id', $course->id)
        ->where('student_id', $student->id)
        ->count();
    
    expect($enrollmentCount)->toBe(1);
});

test('enroll endpoint requires authentication', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Make request without authentication
    $response = $this->postJson("/api/v1/courses/{$course->id}/enroll");
    
    // Assert response status is 401 Unauthorized
    $response->assertStatus(401);
});

test('enroll endpoint returns 404 for non-existent course', function () {
    // Create a verified student
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request with non-existent course ID
    $response = $this->postJson('/api/v1/courses/99999/enroll');
    
    // Assert response status is 404 Not Found
    $response->assertStatus(404);
});

test('myEnrollments endpoint returns enrolled courses with 200 status', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create([
        'name' => 'John Instructor',
    ]);
    
    // Create courses
    $course1 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Course 1',
    ]);
    
    $course2 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Course 2',
    ]);
    
    $course3 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Course 3',
    ]);
    
    // Create a verified student
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Enroll student in course1 and course2
    $student->courses()->attach($course1->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 25,
    ]);
    
    $student->courses()->attach($course2->id, [
        'enrolled_at' => now()->subDays(5),
        'status' => 'active',
        'progress_percentage' => 50,
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request to myEnrollments endpoint
    $response = $this->getJson('/api/v1/student/courses');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response structure
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'title',
                'description',
                'price',
                'duration_hours',
                'level',
                'category',
                'instructor' => [
                    'id',
                    'name',
                    'email',
                    'bio',
                ],
                'enrollment' => [
                    'enrolled_at',
                    'status',
                    'progress_percentage',
                ],
            ],
        ],
    ]);
    
    // Assert only 2 courses are returned (the ones student is enrolled in)
    expect($response->json('data'))->toHaveCount(2);
    
    // Assert course1 is in the response
    $response->assertJsonFragment([
        'title' => 'Course 1',
    ]);
    
    // Assert course2 is in the response
    $response->assertJsonFragment([
        'title' => 'Course 2',
    ]);
    
    // Assert enrollment metadata is included
    $courses = $response->json('data');
    foreach ($courses as $course) {
        expect($course['enrollment'])->toHaveKeys(['enrolled_at', 'status', 'progress_percentage']);
        expect($course['enrollment']['status'])->toBe('active');
    }
});

test('myEnrollments endpoint returns empty array when student has no enrollments', function () {
    // Create a verified student with no enrollments
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request to myEnrollments endpoint
    $response = $this->getJson('/api/v1/student/courses');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert empty data array
    expect($response->json('data'))->toBeArray();
    expect($response->json('data'))->toHaveCount(0);
});

test('myEnrollments endpoint only returns courses for authenticated student', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create courses
    $course1 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Course 1',
    ]);
    
    $course2 = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Course 2',
    ]);
    
    // Create two students
    $student1 = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $student2 = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Enroll student1 in course1
    $student1->courses()->attach($course1->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
    
    // Enroll student2 in course2
    $student2->courses()->attach($course2->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
    
    // Authenticate as student1
    $this->actingAs($student1, 'sanctum');
    
    // Make request to myEnrollments endpoint
    $response = $this->getJson('/api/v1/student/courses');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert only 1 course is returned (student1's enrollment)
    expect($response->json('data'))->toHaveCount(1);
    
    // Assert only course1 is in the response
    $response->assertJsonFragment([
        'title' => 'Course 1',
    ]);
    
    // Assert course2 is NOT in the response
    $courses = $response->json('data');
    foreach ($courses as $course) {
        expect($course['title'])->not->toBe('Course 2');
    }
});

test('myEnrollments endpoint requires authentication', function () {
    // Make request without authentication
    $response = $this->getJson('/api/v1/student/courses');
    
    // Assert response status is 401 Unauthorized
    $response->assertStatus(401);
});

test('myEnrollments endpoint includes instructor data', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create([
        'name' => 'Jane Instructor',
        'email' => 'jane@instructor.com',
        'bio' => 'Expert instructor',
    ]);
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
        'title' => 'Test Course',
    ]);
    
    // Create a verified student
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Enroll student in course
    $student->courses()->attach($course->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request to myEnrollments endpoint
    $response = $this->getJson('/api/v1/student/courses');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert instructor data is included
    $response->assertJsonFragment([
        'name' => 'Jane Instructor',
        'email' => 'jane@instructor.com',
        'bio' => 'Expert instructor',
    ]);
    
    // Get the first course from response
    $courseData = $response->json('data.0');
    
    // Assert instructor is loaded
    expect($courseData['instructor'])->not->toBeNull();
    expect($courseData['instructor']['name'])->toBe('Jane Instructor');
});

test('unenroll endpoint successfully unenrolls student with 200 status', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Create a verified student
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Enroll the student in the course
    $student->courses()->attach($course->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 50,
    ]);
    
    // Verify enrollment exists
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course->id,
        'student_id' => $student->id,
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request to unenroll endpoint
    $response = $this->deleteJson("/api/v1/courses/{$course->id}/unenroll");
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert success message
    $response->assertJsonFragment([
        'message' => 'Successfully unenrolled from course',
    ]);
    
    // Assert enrollment no longer exists in database
    $this->assertDatabaseMissing('course_student', [
        'course_id' => $course->id,
        'student_id' => $student->id,
    ]);
});

test('unenroll endpoint returns 404 when student is not enrolled', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Create a verified student (not enrolled in the course)
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request to unenroll endpoint
    $response = $this->deleteJson("/api/v1/courses/{$course->id}/unenroll");
    
    // Assert response status is 404 Not Found
    $response->assertStatus(404);
    
    // Assert error message
    $response->assertJsonFragment([
        'message' => 'You are not enrolled in this course',
    ]);
});

test('unenroll endpoint requires authentication', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Make request without authentication
    $response = $this->deleteJson("/api/v1/courses/{$course->id}/unenroll");
    
    // Assert response status is 401 Unauthorized
    $response->assertStatus(401);
});

test('unenroll endpoint returns 404 for non-existent course', function () {
    // Create a verified student
    $student = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Authenticate as the student
    $this->actingAs($student, 'sanctum');
    
    // Make request with non-existent course ID
    $response = $this->deleteJson('/api/v1/courses/99999/unenroll');
    
    // Assert response status is 404 Not Found
    $response->assertStatus(404);
});

test('unenroll endpoint only affects authenticated student enrollment', function () {
    // Create an instructor
    $instructor = Instructors::factory()->create();
    
    // Create a course
    $course = Courses::factory()->create([
        'instructor_id' => $instructor->id,
    ]);
    
    // Create two students
    $student1 = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $student2 = \App\Models\Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Enroll both students in the course
    $student1->courses()->attach($course->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
    
    $student2->courses()->attach($course->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 0,
    ]);
    
    // Authenticate as student1
    $this->actingAs($student1, 'sanctum');
    
    // Make request to unenroll endpoint
    $response = $this->deleteJson("/api/v1/courses/{$course->id}/unenroll");
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert student1's enrollment is removed
    $this->assertDatabaseMissing('course_student', [
        'course_id' => $course->id,
        'student_id' => $student1->id,
    ]);
    
    // Assert student2's enrollment still exists
    $this->assertDatabaseHas('course_student', [
        'course_id' => $course->id,
        'student_id' => $student2->id,
    ]);
});
