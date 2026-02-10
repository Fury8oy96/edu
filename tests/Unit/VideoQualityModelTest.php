<?php

namespace Tests\Unit;

use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoQualityModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test VideoQuality model can be created with required fields
     */
    public function test_video_quality_model_can_be_created(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
            'status' => 'pending',
        ]);

        $videoQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test/720p.mp4',
            'file_size' => 512000,
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(VideoQuality::class, $videoQuality);
        $this->assertEquals($video->id, $videoQuality->video_id);
        $this->assertEquals('720p', $videoQuality->quality);
        $this->assertEquals('videos/test/720p.mp4', $videoQuality->file_path);
        $this->assertEquals(512000, $videoQuality->file_size);
        $this->assertEquals('pending', $videoQuality->status);
    }

    /**
     * Test file_size is cast to integer
     */
    public function test_file_size_is_cast_to_integer(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
        ]);

        $videoQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test/720p.mp4',
            'file_size' => '512000',
        ]);

        $this->assertIsInt($videoQuality->file_size);
        $this->assertEquals(512000, $videoQuality->file_size);
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
        ]);

        $videoQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test/720p.mp4',
            'file_size' => 512000,
            'processing_progress' => 75,
        ]);

        $this->assertIsInt($videoQuality->processing_progress);
        $this->assertEquals(75, $videoQuality->processing_progress);
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
        ]);

        $videoQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test/720p.mp4',
            'file_size' => 512000,
            'status' => 'completed',
        ]);

        $this->assertTrue($videoQuality->isCompleted());
    }

    /**
     * Test isCompleted returns false for pending status
     */
    public function test_is_completed_returns_false_for_pending_status(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
        ]);

        $videoQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test/720p.mp4',
            'file_size' => 512000,
            'status' => 'pending',
        ]);

        $this->assertFalse($videoQuality->isCompleted());
    }

    /**
     * Test isCompleted returns false for processing status
     */
    public function test_is_completed_returns_false_for_processing_status(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
        ]);

        $videoQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test/720p.mp4',
            'file_size' => 512000,
            'status' => 'processing',
        ]);

        $this->assertFalse($videoQuality->isCompleted());
    }

    /**
     * Test isCompleted returns false for failed status
     */
    public function test_is_completed_returns_false_for_failed_status(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
        ]);

        $videoQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test/720p.mp4',
            'file_size' => 512000,
            'status' => 'failed',
        ]);

        $this->assertFalse($videoQuality->isCompleted());
    }

    /**
     * Test video relationship returns Video instance
     */
    public function test_video_relationship_returns_video(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
        ]);

        $videoQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test/720p.mp4',
            'file_size' => 512000,
        ]);

        $this->assertInstanceOf(Video::class, $videoQuality->video);
        $this->assertEquals($video->id, $videoQuality->video->id);
    }

    /**
     * Test video can have multiple quality levels
     */
    public function test_video_can_have_multiple_quality_levels(): void
    {
        $video = Video::create([
            'original_filename' => 'test_video.mp4',
            'display_name' => 'Test Video',
            'file_size' => 1024000,
            'original_path' => 'videos/test/original.mp4',
        ]);

        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '360p',
            'file_path' => 'videos/test/360p.mp4',
            'file_size' => 256000,
        ]);

        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test/720p.mp4',
            'file_size' => 512000,
        ]);

        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '1080p',
            'file_path' => 'videos/test/1080p.mp4',
            'file_size' => 1024000,
        ]);

        $this->assertCount(3, $video->qualities);
        $this->assertEquals(['360p', '720p', '1080p'], $video->qualities->pluck('quality')->toArray());
    }
}
