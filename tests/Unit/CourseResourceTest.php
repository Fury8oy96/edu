<?php

use App\Http\Resources\CourseResource;
use App\Http\Resources\InstructorResource;
use App\Models\Courses;
use App\Models\Instructors;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('CourseResource transforms course model correctly', function () {
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
    $resource = new CourseResource($course);
    $array = $resource->toArray(request());
    
    // Assert all required fields are present
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

test('CourseResource includes instructor when loaded', function () {
    // Create an instructor
    $instructor = Instructors::create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'bio' => 'Expert developer',
        'avatar' => 'jane.jpg',
    ]);
    
    // Create a course with instructor loaded
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
        'requirements' => 'None',
        'outcomes' => 'Learn PHP',
        'target_audience' => 'Beginners',
        'instructor_id' => $instructor->id,
        'enrollment_count' => 75,
        'is_paid' => true,
        'status' => 'published',
        'language' => 'English',
    ]);
    
    // Load instructor relationship
    $course->load('instructor');
    
    // Transform using resource
    $resource = new CourseResource($course);
    $array = $resource->toArray(request());
    
    // Assert instructor is included
    expect($array)->toHaveKey('instructor');
    expect($array['instructor'])->toBeInstanceOf(InstructorResource::class);
});

test('CourseResource handles missing instructor gracefully', function () {
    // Create an instructor first
    $instructor = Instructors::create([
        'name' => 'Test Instructor',
        'email' => 'test@example.com',
        'bio' => 'Test bio',
        'avatar' => 'test.jpg',
    ]);
    
    // Create a course without loading instructor
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
    
    // Transform without loading instructor
    $resource = new CourseResource($course);
    $array = $resource->toArray(request());
    
    // Assert instructor field exists but is handled by whenLoaded
    expect($array)->toHaveKey('instructor');
});
