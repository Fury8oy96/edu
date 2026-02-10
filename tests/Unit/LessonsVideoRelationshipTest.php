<?php

namespace Tests\Unit;

use App\Models\Lessons;
use App\Models\Video;
use App\Models\Instructors;
use App\Models\Modules;
use App\Models\Courses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonsVideoRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Lessons model has videos relationship
     */
    public function test_lessons_model_has_videos_relationship(): void
    {
        // Create necessary dependencies
        $instructor = Instructors::factory()->create();
        $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
        $module = Modules::factory()->create(['course_id' => $course->id]);
        
        $lesson = Lessons::create([
            'title' => 'Test Lesson',
            'description' => 'Test Description',
            'duration' => 60,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
        ]);

        $this->assertTrue(method_exists($lesson, 'videos'));
    }

    /**
     * Test videos relationship returns BelongsToMany instance
     */
    public function test_videos_relationship_returns_belongs_to_many(): void
    {
        // Create necessary dependencies
        $instructor = Instructors::factory()->create();
        $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
        $module = Modules::factory()->create(['course_id' => $course->id]);
        
        $lesson = Lessons::create([
            'title' => 'Test Lesson',
            'description' => 'Test Description',
            'duration' => 60,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
        ]);

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $lesson->videos()
        );
    }

    /**
     * Test lesson can be associated with video through pivot table
     */
    public function test_lesson_can_be_associated_with_video(): void
    {
        // Create necessary dependencies
        $instructor = Instructors::factory()->create();
        $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
        $module = Modules::factory()->create(['course_id' => $course->id]);
        
        $lesson = Lessons::create([
            'title' => 'Test Lesson',
            'description' => 'Test Description',
            'duration' => 60,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
        ]);

        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test.mp4',
            'status' => 'completed',
        ]);

        // Attach video to lesson
        $lesson->videos()->attach($video->id, ['attached_at' => now()]);

        // Verify the association
        $this->assertEquals(1, $lesson->videos()->count());
        $this->assertEquals($video->id, $lesson->videos()->first()->id);
    }

    /**
     * Test lesson can have multiple videos
     */
    public function test_lesson_can_have_multiple_videos(): void
    {
        // Create necessary dependencies
        $instructor = Instructors::factory()->create();
        $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
        $module = Modules::factory()->create(['course_id' => $course->id]);
        
        $lesson = Lessons::create([
            'title' => 'Test Lesson',
            'description' => 'Test Description',
            'duration' => 60,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
        ]);

        $video1 = Video::create([
            'original_filename' => 'test1.mp4',
            'display_name' => 'Test Video 1',
            'file_size' => 1024000,
            'original_path' => 'videos/test1.mp4',
            'status' => 'completed',
        ]);

        $video2 = Video::create([
            'original_filename' => 'test2.mp4',
            'display_name' => 'Test Video 2',
            'file_size' => 2048000,
            'original_path' => 'videos/test2.mp4',
            'status' => 'completed',
        ]);

        // Attach both videos to lesson
        $lesson->videos()->attach($video1->id, ['attached_at' => now()]);
        $lesson->videos()->attach($video2->id, ['attached_at' => now()]);

        // Verify the associations
        $this->assertEquals(2, $lesson->videos()->count());
        $this->assertTrue($lesson->videos->contains($video1));
        $this->assertTrue($lesson->videos->contains($video2));
    }

    /**
     * Test video-lesson relationship is bidirectional
     */
    public function test_video_lesson_relationship_is_bidirectional(): void
    {
        // Create necessary dependencies
        $instructor = Instructors::factory()->create();
        $course = Courses::factory()->create(['instructor_id' => $instructor->id]);
        $module = Modules::factory()->create(['course_id' => $course->id]);
        
        $lesson = Lessons::create([
            'title' => 'Test Lesson',
            'description' => 'Test Description',
            'duration' => 60,
            'module_id' => $module->id,
            'instructor_id' => $instructor->id,
        ]);

        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test.mp4',
            'status' => 'completed',
        ]);

        // Attach from lesson side
        $lesson->videos()->attach($video->id, ['attached_at' => now()]);

        // Verify from both sides
        $this->assertEquals(1, $lesson->videos()->count());
        $this->assertEquals(1, $video->lessons()->count());
        $this->assertEquals($video->id, $lesson->videos()->first()->id);
        $this->assertEquals($lesson->id, $video->lessons()->first()->id);
    }
}
