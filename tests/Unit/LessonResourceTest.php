<?php

use App\Http\Resources\LessonResource;
use App\Models\Courses;
use App\Models\Instructors;
use App\Models\Lessons;
use App\Models\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('LessonResource transforms lesson model correctly', function () {
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
    
    // Create a lesson
    $lesson = Lessons::create([
        'title' => 'Installing Laravel',
        'description' => 'Learn how to install Laravel on your system',
        'video_url' => 'https://example.com/install-laravel.mp4',
        'duration' => 15,
        'module_id' => $module->id,
        'instructor_id' => $instructor->id,
        'outcomes' => 'Successfully install Laravel',
        'keywords' => ['installation', 'setup'],
        'requirements' => 'PHP installed',
        'tags' => ['setup'],
    ]);
    
    // Transform using resource
    $resource = new LessonResource($lesson);
    $array = $resource->toArray(request());
    
    // Assert all required fields are present
    expect($array)->toHaveKey('id');
    expect($array)->toHaveKey('title');
    expect($array)->toHaveKey('description');
    expect($array)->toHaveKey('duration');
    expect($array)->toHaveKey('video_url');
    
    // Assert values are correct
    expect($array['id'])->toBe($lesson->id);
    expect($array['title'])->toBe('Installing Laravel');
    expect($array['description'])->toBe('Learn how to install Laravel on your system');
    expect($array['duration'])->toBe(15);
    expect($array['video_url'])->toBe('https://example.com/install-laravel.mp4');
});

test('LessonResource handles null video_url gracefully', function () {
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
    
    // Create a lesson without video_url
    $lesson = Lessons::create([
        'title' => 'Reading Assignment',
        'description' => 'Read the PHP documentation',
        'video_url' => null,
        'duration' => 30,
        'module_id' => $module->id,
        'instructor_id' => $instructor->id,
        'outcomes' => 'Understand PHP documentation',
        'keywords' => ['documentation'],
        'requirements' => 'None',
        'tags' => ['reading'],
    ]);
    
    // Transform using resource
    $resource = new LessonResource($lesson);
    $array = $resource->toArray(request());
    
    // Assert video_url is null
    expect($array)->toHaveKey('video_url');
    expect($array['video_url'])->toBeNull();
});
