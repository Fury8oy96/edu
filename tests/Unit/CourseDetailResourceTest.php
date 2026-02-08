<?php

use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\InstructorResource;
use App\Models\Courses;
use App\Models\Instructors;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('CourseDetailResource includes all base CourseResource fields', function () {
    // Create an instructor
    $instructor = Instructors::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'bio' => 'Experienced instructor',
        'avatar' => 'avatar.jpg',
    ]);
    
    // Create a course
    $course = Courses::create([
        'title' => 'Laravel Mastery',
        'description' => 'Learn Laravel from scratch',
        'image' => 'course.jpg',
        'price' => 99.99,
        'duration_hours' => 40,
        'level' => 'intermediate',
        'category' => 'Web Development',
        'subcategory' => 'Backend',
        'tags' => ['laravel', 'php'],
        'keywords' => ['web', 'backend'],
        'requirements' => 'Basic PHP knowledge',
        'outcomes' => 'Build web applications',
        'target_audience' => 'Developers',
        'instructor_id' => $instructor->id,
        'enrollment_count' => 150,
        'is_paid' => true,
        'status' => 'published',
        'language' => 'English',
    ]);
    
    // Transform using resource
    $resource = new CourseDetailResource($course);
    $array = $resource->toArray(request());
    
    // Assert all base CourseResource fields are present
    expect($array)->toHaveKey('id');
    expect($array)->toHaveKey('title');
    expect($array)->toHaveKey('description');
    expect($array)->toHaveKey('price');
    expect($array)->toHaveKey('duration_hours');
    expect($array)->toHaveKey('level');
    expect($array)->toHaveKey('category');
    expect($array)->toHaveKey('enrollment_count');
    expect($array)->toHaveKey('created_at');
    
    // Assert values are correct
    expect($array['id'])->toBe($course->id);
    expect($array['title'])->toBe('Laravel Mastery');
    expect($array['description'])->toBe('Learn Laravel from scratch');
    expect($array['price'])->toBe('99.99');
    expect($array['duration_hours'])->toBe(40);
    expect($array['level'])->toBe('intermediate');
    expect($array['category'])->toBe('Web Development');
    expect($array['enrollment_count'])->toBe(150);
});

test('CourseDetailResource includes additional detail fields', function () {
    // Create an instructor
    $instructor = Instructors::create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'bio' => 'Expert developer',
        'avatar' => 'jane.jpg',
    ]);
    
    // Create a course
    $course = Courses::create([
        'title' => 'PHP Fundamentals',
        'description' => 'Master PHP basics',
        'image' => 'php.jpg',
        'price' => 49.99,
        'duration_hours' => 20,
        'level' => 'beginner',
        'category' => 'Programming',
        'subcategory' => 'Languages',
        'tags' => ['php'],
        'keywords' => ['programming'],
        'requirements' => 'No prior programming experience needed',
        'outcomes' => 'Understand PHP syntax and build simple applications',
        'target_audience' => 'Beginners who want to learn web development',
        'instructor_id' => $instructor->id,
        'enrollment_count' => 75,
        'is_paid' => true,
        'status' => 'published',
        'language' => 'English',
    ]);
    
    // Transform using resource
    $resource = new CourseDetailResource($course);
    $array = $resource->toArray(request());
    
    // Assert additional detail fields are present
    expect($array)->toHaveKey('requirements');
    expect($array)->toHaveKey('outcomes');
    expect($array)->toHaveKey('target_audience');
    expect($array)->toHaveKey('modules');
    
    // Assert values are correct
    expect($array['requirements'])->toBe('No prior programming experience needed');
    expect($array['outcomes'])->toBe('Understand PHP syntax and build simple applications');
    expect($array['target_audience'])->toBe('Beginners who want to learn web development');
});

test('CourseDetailResource includes instructor when loaded', function () {
    // Create an instructor
    $instructor = Instructors::create([
        'name' => 'Test Instructor',
        'email' => 'test@example.com',
        'bio' => 'Test bio',
        'avatar' => 'test.jpg',
    ]);
    
    // Create a course with instructor loaded
    $course = Courses::create([
        'title' => 'Test Course',
        'description' => 'Test description',
        'image' => 'test.jpg',
        'price' => 29.99,
        'duration_hours' => 10,
        'level' => 'beginner',
        'category' => 'Testing',
        'subcategory' => 'QA',
        'tags' => ['test'],
        'keywords' => ['testing'],
        'requirements' => 'None',
        'outcomes' => 'Testing skills',
        'target_audience' => 'Testers',
        'instructor_id' => $instructor->id,
        'enrollment_count' => 0,
        'is_paid' => true,
        'status' => 'published',
        'language' => 'English',
    ]);
    
    // Load instructor relationship
    $course->load('instructor');
    
    // Transform using resource
    $resource = new CourseDetailResource($course);
    $array = $resource->toArray(request());
    
    // Assert instructor is included
    expect($array)->toHaveKey('instructor');
    expect($array['instructor'])->toBeInstanceOf(InstructorResource::class);
});

test('CourseDetailResource handles null detail fields gracefully', function () {
    // Create an instructor
    $instructor = Instructors::create([
        'name' => 'Minimal Instructor',
        'email' => 'minimal@example.com',
        'bio' => 'Bio',
        'avatar' => 'avatar.jpg',
    ]);
    
    // Create a course with empty detail fields
    $course = Courses::create([
        'title' => 'Minimal Course',
        'description' => 'Minimal description',
        'image' => 'minimal.jpg',
        'price' => 0.00,
        'duration_hours' => 5,
        'level' => 'beginner',
        'category' => 'General',
        'subcategory' => 'Other',
        'tags' => [],
        'keywords' => [],
        'requirements' => '',
        'outcomes' => '',
        'target_audience' => '',
        'instructor_id' => $instructor->id,
        'enrollment_count' => 0,
        'is_paid' => false,
        'status' => 'draft',
        'language' => 'English',
    ]);
    
    // Transform using resource
    $resource = new CourseDetailResource($course);
    $array = $resource->toArray(request());
    
    // Assert detail fields are present
    expect($array)->toHaveKey('requirements');
    expect($array)->toHaveKey('outcomes');
    expect($array)->toHaveKey('target_audience');
    
    // Assert empty string values are preserved
    expect($array['requirements'])->toBe('');
    expect($array['outcomes'])->toBe('');
    expect($array['target_audience'])->toBe('');
});
