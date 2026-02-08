<?php

use App\Http\Resources\LessonResource;
use App\Http\Resources\ModuleResource;
use App\Models\Courses;
use App\Models\Instructors;
use App\Models\Lessons;
use App\Models\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ModuleResource transforms module model correctly', function () {
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
    
    // Create a module
    $module = Modules::create([
        'title' => 'Introduction to Laravel',
        'description' => 'Getting started with Laravel framework',
        'course_id' => $course->id,
        'duration' => 120,
        'status' => 'published',
        'keywords' => ['laravel', 'introduction'],
        'requirements' => 'None',
        'outcomes' => 'Understand Laravel basics',
        'tags' => ['basics'],
    ]);
    
    // Transform using resource
    $resource = new ModuleResource($module);
    $array = $resource->toArray(request());
    
    // Assert all required fields are present
    expect($array)->toHaveKey('id');
    expect($array)->toHaveKey('title');
    expect($array)->toHaveKey('description');
    expect($array)->toHaveKey('duration');
    expect($array)->toHaveKey('lessons');
    
    // Assert values are correct
    expect($array['id'])->toBe($module->id);
    expect($array['title'])->toBe('Introduction to Laravel');
    expect($array['description'])->toBe('Getting started with Laravel framework');
    expect($array['duration'])->toBe(120);
});

test('ModuleResource includes lessons when loaded', function () {
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
        'requirements' => 'None',
        'outcomes' => 'Learn PHP',
        'target_audience' => 'Beginners',
        'instructor_id' => $instructor->id,
        'enrollment_count' => 75,
        'is_paid' => true,
        'status' => 'published',
        'language' => 'English',
    ]);
    
    // Create a module
    $module = Modules::create([
        'title' => 'PHP Basics',
        'description' => 'Learn PHP fundamentals',
        'course_id' => $course->id,
        'duration' => 180,
        'status' => 'published',
        'keywords' => ['php', 'basics'],
        'requirements' => 'None',
        'outcomes' => 'Understand PHP syntax',
        'tags' => ['fundamentals'],
    ]);
    
    // Create lessons for the module
    $lesson1 = Lessons::create([
        'title' => 'Variables and Data Types',
        'description' => 'Learn about PHP variables',
        'video_url' => 'https://example.com/video1.mp4',
        'duration' => 30,
        'module_id' => $module->id,
        'instructor_id' => $instructor->id,
        'outcomes' => 'Understand variables',
        'keywords' => ['variables'],
        'requirements' => 'None',
        'tags' => ['basics'],
    ]);
    
    $lesson2 = Lessons::create([
        'title' => 'Control Structures',
        'description' => 'Learn about if statements and loops',
        'video_url' => 'https://example.com/video2.mp4',
        'duration' => 45,
        'module_id' => $module->id,
        'instructor_id' => $instructor->id,
        'outcomes' => 'Use control structures',
        'keywords' => ['control', 'loops'],
        'requirements' => 'Variables knowledge',
        'tags' => ['control-flow'],
    ]);
    
    // Load lessons relationship
    $module->load('lessons');
    
    // Transform using resource
    $resource = new ModuleResource($module);
    $array = $resource->toArray(request());
    
    // Assert lessons are included
    expect($array)->toHaveKey('lessons');
    expect($array['lessons'])->toBeInstanceOf(\Illuminate\Http\Resources\Json\AnonymousResourceCollection::class);
    
    // Convert to array to check count
    $lessonsArray = $array['lessons']->toArray(request());
    expect($lessonsArray)->toHaveCount(2);
});

test('ModuleResource handles missing lessons gracefully', function () {
    // Create an instructor
    $instructor = Instructors::create([
        'name' => 'Test Instructor',
        'email' => 'test@example.com',
        'bio' => 'Test bio',
        'avatar' => 'test.jpg',
    ]);
    
    // Create a course
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
    
    // Create a module without loading lessons
    $module = Modules::create([
        'title' => 'Test Module',
        'description' => 'Test module description',
        'course_id' => $course->id,
        'duration' => 60,
        'status' => 'published',
        'keywords' => ['test'],
        'requirements' => 'None',
        'outcomes' => 'Test outcomes',
        'tags' => ['test'],
    ]);
    
    // Transform without loading lessons
    $resource = new ModuleResource($module);
    $array = $resource->toArray(request());
    
    // Assert lessons field exists but is handled by whenLoaded
    expect($array)->toHaveKey('lessons');
});
