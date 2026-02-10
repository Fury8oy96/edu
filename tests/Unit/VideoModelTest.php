<?php

namespace Tests\Unit;

use App\Models\Video;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Video model can be created with required fields
     */
    public function test_video_model_can_be_created(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Video::class, $video);
        $this->assertEquals('test_video.mp4', $video->original_filename);
        $this->assertEquals('Test Video', $video->display_name);
        $this->assertEquals(1024000, $video->file_size);
        $this->assertEquals('pending', $video->status);
    }

    /**
     * Test duration is cast to decimal
     */
    public function test_duration_is_cast_to_decimal(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'duration' => 123.45,
            'original_path' => 'videos/test/original.mp4',
        ]);

        $this->assertIsString($video->duration);
        $this->assertEquals('123.45', $video->duration);
    }

    /**
     * Test processing_progress is cast to integer
     */
    public function test_processing_progress_is_cast_to_integer(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'processing_progress' => 50,
        ]);

        $this->assertIsInt($video->processing_progress);
        $this->assertEquals(50, $video->processing_progress);
    }

    /**
     * Test isProcessing returns true for pending status
     */
    public function test_is_processing_returns_true_for_pending_status(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'status' => 'pending',
        ]);

        $this->assertTrue($video->isProcessing());
    }

    /**
     * Test isProcessing returns true for processing status
     */
    public function test_is_processing_returns_true_for_processing_status(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'status' => 'processing',
        ]);

        $this->assertTrue($video->isProcessing());
    }

    /**
     * Test isProcessing returns false for completed status
     */
    public function test_is_processing_returns_false_for_completed_status(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'status' => 'completed',
        ]);

        $this->assertFalse($video->isProcessing());
    }

    /**
     * Test isProcessing returns false for failed status
     */
    public function test_is_processing_returns_false_for_failed_status(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'status' => 'failed',
        ]);

        $this->assertFalse($video->isProcessing());
    }

    /**
     * Test isCompleted returns true for completed status
     */
    public function test_is_completed_returns_true_for_completed_status(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'status' => 'completed',
        ]);

        $this->assertTrue($video->isCompleted());
    }

    /**
     * Test isCompleted returns false for non-completed status
     */
    public function test_is_completed_returns_false_for_non_completed_status(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'status' => 'pending',
        ]);

        $this->assertFalse($video->isCompleted());
    }

    /**
     * Test hasActiveLessons returns false when no lessons are associated
     */
    public function test_has_active_lessons_returns_false_when_no_lessons(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
        ]);

        $this->assertFalse($video->hasActiveLessons());
    }

    /**
     * Test uploader relationship returns User instance
     */
    public function test_uploader_relationship_returns_user(): void
    {
        $user = User::factory()->create();
        
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'uploaded_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $video->uploader);
        $this->assertEquals($user->id, $video->uploader->id);
    }
}
