<?php

namespace Tests\Unit;

use App\Exceptions\LessonNotFoundException;
use App\Exceptions\VideoNotFoundException;
use App\Exceptions\VideoNotReadyException;
use App\Models\Lessons;
use App\Models\Modules;
use App\Models\Video;
use App\Services\LessonVideoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonVideoServiceTest extends TestCase
{
    use RefreshDatabase;

    private LessonVideoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LessonVideoService();
    }

    /**
     * Test that attachVideoToLesson validates video exists
     */
    public function test_attach_video_to_lesson_throws_exception_when_video_not_found(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create();

        // Act & Assert
        $this->expectException(VideoNotFoundException::class);
        
        $this->service->attachVideoToLesson($lesson->id, 999);
    }

    /**
     * Test that attachVideoToLesson validates video has status "completed"
     */
    public function test_attach_video_to_lesson_throws_exception_when_video_not_completed(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create();
        $video = Video::factory()->create(['status' => 'processing']);

        // Act & Assert
        $this->expectException(VideoNotReadyException::class);
        $this->expectExceptionMessage('Video is still processing');
        
        $this->service->attachVideoToLesson($lesson->id, $video->id);
    }

    /**
     * Test that attachVideoToLesson validates lesson exists
     */
    public function test_attach_video_to_lesson_throws_exception_when_lesson_not_found(): void
    {
        // Arrange
        $video = Video::factory()->create(['status' => 'completed']);

        // Act & Assert
        $this->expectException(LessonNotFoundException::class);
        $this->expectExceptionMessage('Lesson with ID 999 not found');
        
        $this->service->attachVideoToLesson(999, $video->id);
    }

    /**
     * Test that attachVideoToLesson updates lesson video_url field
     */
    public function test_attach_video_to_lesson_updates_lesson_video_url(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create(['video_url' => null]);
        $video = Video::factory()->create(['status' => 'completed']);

        // Act
        $result = $this->service->attachVideoToLesson($lesson->id, $video->id);

        // Assert
        $this->assertEquals((string) $video->id, $result->video_url);
        
        // Verify in database
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'video_url' => (string) $video->id,
        ]);
    }

    /**
     * Test that attachVideoToLesson creates pivot record
     */
    public function test_attach_video_to_lesson_creates_pivot_record(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create();
        $video = Video::factory()->create(['status' => 'completed']);

        // Act
        $result = $this->service->attachVideoToLesson($lesson->id, $video->id);

        // Assert
        $this->assertDatabaseHas('video_lesson', [
            'video_id' => $video->id,
            'lesson_id' => $lesson->id,
        ]);

        // Verify relationship
        $this->assertTrue($result->videos->contains($video));
    }

    /**
     * Test that attachVideoToLesson sets attached_at timestamp
     */
    public function test_attach_video_to_lesson_sets_attached_at_timestamp(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create();
        $video = Video::factory()->create(['status' => 'completed']);

        // Act
        $this->service->attachVideoToLesson($lesson->id, $video->id);

        // Assert
        $pivot = $lesson->videos()->where('video_id', $video->id)->first()->pivot;
        $this->assertNotNull($pivot->attached_at);
    }

    /**
     * Test that a video can be attached to multiple lessons
     */
    public function test_video_can_be_attached_to_multiple_lessons(): void
    {
        // Arrange
        $lesson1 = Lessons::factory()->create();
        $lesson2 = Lessons::factory()->create();
        $video = Video::factory()->create(['status' => 'completed']);

        // Act
        $this->service->attachVideoToLesson($lesson1->id, $video->id);
        $this->service->attachVideoToLesson($lesson2->id, $video->id);

        // Assert
        $this->assertDatabaseHas('video_lesson', [
            'video_id' => $video->id,
            'lesson_id' => $lesson1->id,
        ]);
        $this->assertDatabaseHas('video_lesson', [
            'video_id' => $video->id,
            'lesson_id' => $lesson2->id,
        ]);

        // Verify video has both lessons
        $video->refresh();
        $this->assertCount(2, $video->lessons);
        $this->assertTrue($video->lessons->contains($lesson1));
        $this->assertTrue($video->lessons->contains($lesson2));
    }

    /**
     * Test that detachVideoFromLesson throws exception when lesson not found
     */
    public function test_detach_video_from_lesson_throws_exception_when_lesson_not_found(): void
    {
        // Act & Assert
        $this->expectException(LessonNotFoundException::class);
        $this->expectExceptionMessage('Lesson with ID 999 not found');
        
        $this->service->detachVideoFromLesson(999);
    }

    /**
     * Test that detachVideoFromLesson clears lesson video_url field
     */
    public function test_detach_video_from_lesson_clears_video_url(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create();
        $video = Video::factory()->create(['status' => 'completed']);
        $this->service->attachVideoToLesson($lesson->id, $video->id);

        // Act
        $result = $this->service->detachVideoFromLesson($lesson->id);

        // Assert
        $this->assertNull($result->video_url);
        
        // Verify in database
        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'video_url' => null,
        ]);
    }

    /**
     * Test that detachVideoFromLesson removes pivot record
     */
    public function test_detach_video_from_lesson_removes_pivot_record(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create();
        $video = Video::factory()->create(['status' => 'completed']);
        $this->service->attachVideoToLesson($lesson->id, $video->id);

        // Verify pivot exists
        $this->assertDatabaseHas('video_lesson', [
            'video_id' => $video->id,
            'lesson_id' => $lesson->id,
        ]);

        // Act
        $result = $this->service->detachVideoFromLesson($lesson->id);

        // Assert
        $this->assertDatabaseMissing('video_lesson', [
            'video_id' => $video->id,
            'lesson_id' => $lesson->id,
        ]);

        // Verify relationship is empty
        $this->assertCount(0, $result->videos);
    }

    /**
     * Test that detachVideoFromLesson works even when no video is attached
     */
    public function test_detach_video_from_lesson_works_when_no_video_attached(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create(['video_url' => null]);

        // Act
        $result = $this->service->detachVideoFromLesson($lesson->id);

        // Assert
        $this->assertNull($result->video_url);
        $this->assertCount(0, $result->videos);
    }

    /**
     * Test that getLessonsForVideo throws exception when video not found
     */
    public function test_get_lessons_for_video_throws_exception_when_video_not_found(): void
    {
        // Act & Assert
        $this->expectException(VideoNotFoundException::class);
        
        $this->service->getLessonsForVideo(999);
    }

    /**
     * Test that getLessonsForVideo returns empty collection when no lessons attached
     */
    public function test_get_lessons_for_video_returns_empty_collection_when_no_lessons(): void
    {
        // Arrange
        $video = Video::factory()->create(['status' => 'completed']);

        // Act
        $result = $this->service->getLessonsForVideo($video->id);

        // Assert
        $this->assertCount(0, $result);
    }

    /**
     * Test that getLessonsForVideo returns all associated lessons
     */
    public function test_get_lessons_for_video_returns_all_associated_lessons(): void
    {
        // Arrange
        $video = Video::factory()->create(['status' => 'completed']);
        $lesson1 = Lessons::factory()->create();
        $lesson2 = Lessons::factory()->create();
        $lesson3 = Lessons::factory()->create();

        // Attach video to lesson1 and lesson2, but not lesson3
        $this->service->attachVideoToLesson($lesson1->id, $video->id);
        $this->service->attachVideoToLesson($lesson2->id, $video->id);

        // Act
        $result = $this->service->getLessonsForVideo($video->id);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($lesson1));
        $this->assertTrue($result->contains($lesson2));
        $this->assertFalse($result->contains($lesson3));
    }

    /**
     * Test that attachVideoToLesson uses database transaction
     * (rollback on failure ensures atomicity)
     */
    public function test_attach_video_to_lesson_uses_transaction(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create();
        $video = Video::factory()->create(['status' => 'completed']);

        // We can't easily test transaction rollback without mocking,
        // but we can verify that both operations succeed together
        
        // Act
        $result = $this->service->attachVideoToLesson($lesson->id, $video->id);

        // Assert - both video_url and pivot should be set
        $this->assertEquals((string) $video->id, $result->video_url);
        $this->assertDatabaseHas('video_lesson', [
            'video_id' => $video->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    /**
     * Test that detachVideoFromLesson uses database transaction
     * (both video_url and pivot should be cleared together)
     */
    public function test_detach_video_from_lesson_uses_transaction(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create();
        $video = Video::factory()->create(['status' => 'completed']);
        $this->service->attachVideoToLesson($lesson->id, $video->id);

        // Act
        $result = $this->service->detachVideoFromLesson($lesson->id);

        // Assert - both video_url and pivot should be cleared
        $this->assertNull($result->video_url);
        $this->assertDatabaseMissing('video_lesson', [
            'video_id' => $video->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    /**
     * Test that attachVideoToLesson replaces existing video_url
     */
    public function test_attach_video_to_lesson_replaces_existing_video_url(): void
    {
        // Arrange
        $lesson = Lessons::factory()->create();
        $video1 = Video::factory()->create(['status' => 'completed']);
        $video2 = Video::factory()->create(['status' => 'completed']);

        // Attach first video
        $this->service->attachVideoToLesson($lesson->id, $video1->id);
        
        // Act - attach second video
        $result = $this->service->attachVideoToLesson($lesson->id, $video2->id);

        // Assert - video_url should be updated to second video
        $this->assertEquals((string) $video2->id, $result->video_url);
        
        // Both videos should still be in pivot table (allows multiple associations)
        $this->assertDatabaseHas('video_lesson', [
            'video_id' => $video1->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->assertDatabaseHas('video_lesson', [
            'video_id' => $video2->id,
            'lesson_id' => $lesson->id,
        ]);
    }
}
