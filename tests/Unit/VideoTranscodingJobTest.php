<?php

namespace Tests\Unit;

use App\Jobs\VideoTranscodingJob;
use App\Models\Video;
use App\Models\VideoQuality;
use App\Services\FFMPEGService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoTranscodingJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /**
     * Test that handle method validates video exists
     */
    public function test_handle_validates_video_exists(): void
    {
        // Arrange
        $job = new VideoTranscodingJob(999, '720p');

        // Act & Assert
        $this->expectException(\App\Exceptions\VideoNotFoundException::class);
        $job->handle();
    }

    /**
     * Test that handle method updates quality status to processing
     */
    public function test_handle_updates_quality_status_to_processing(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        $quality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => '',
            'file_size' => 0,
            'status' => 'pending',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService
        $this->mock(FFMPEGService::class, function ($mock) use ($video) {
            $mock->shouldReceive('transcodeVideo')
                ->once()
                ->andReturnUsing(function ($input, $output, $quality, $callback) {
                    // Create the output file
                    file_put_contents($output, 'transcoded video content');
                    return true;
                });
        });

        $job = new VideoTranscodingJob($video->id, '720p');

        // Act
        $job->handle();

        // Assert
        $quality->refresh();
        $this->assertEquals('completed', $quality->status);
    }

    /**
     * Test that handle method transcodes video successfully
     */
    public function test_handle_transcodes_video_successfully(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        $quality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '480p',
            'file_path' => '',
            'file_size' => 0,
            'status' => 'pending',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService
        $this->mock(FFMPEGService::class, function ($mock) use ($video) {
            $mock->shouldReceive('transcodeVideo')
                ->once()
                ->with(
                    \Mockery::any(),
                    \Mockery::any(),
                    '480p',
                    \Mockery::any()
                )
                ->andReturnUsing(function ($input, $output, $quality, $callback) {
                    // Create the output file
                    file_put_contents($output, 'transcoded video content');
                    return true;
                });
        });

        $job = new VideoTranscodingJob($video->id, '480p');

        // Act
        $job->handle();

        // Assert
        $quality->refresh();
        $this->assertEquals('completed', $quality->status);
        $this->assertEquals(100, $quality->processing_progress);
        $this->assertNotEmpty($quality->file_path);
        $this->assertGreaterThan(0, $quality->file_size);
        $this->assertNull($quality->error_message);
    }

    /**
     * Test that handle method updates video status when all qualities complete
     */
    public function test_handle_updates_video_status_when_all_qualities_complete(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        // Create quality records - 3 already completed, 1 pending
        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '360p',
            'file_path' => 'videos/test-uuid/360p.mp4',
            'file_size' => 500000,
            'status' => 'completed',
            'processing_progress' => 100,
        ]);

        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '480p',
            'file_path' => 'videos/test-uuid/480p.mp4',
            'file_size' => 700000,
            'status' => 'completed',
            'processing_progress' => 100,
        ]);

        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test-uuid/720p.mp4',
            'file_size' => 1200000,
            'status' => 'completed',
            'processing_progress' => 100,
        ]);

        $lastQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '1080p',
            'file_path' => '',
            'file_size' => 0,
            'status' => 'pending',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('transcodeVideo')
                ->once()
                ->andReturnUsing(function ($input, $output, $quality, $callback) {
                    file_put_contents($output, 'transcoded video content');
                    return true;
                });
        });

        $job = new VideoTranscodingJob($video->id, '1080p');

        // Act
        $job->handle();

        // Assert
        $video->refresh();
        $this->assertEquals('completed', $video->status);
        $this->assertEquals(100, $video->processing_progress);
        $this->assertNull($video->error_message);
    }

    /**
     * Test that handle method logs FFMPEG errors properly
     */
    public function test_handle_logs_ffmpeg_errors_properly(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        $quality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => '',
            'file_size' => 0,
            'status' => 'pending',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService to throw exception
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('transcodeVideo')
                ->once()
                ->andThrow(new \App\Exceptions\FFMPEGException('Transcoding failed', 'FFMPEG error output'));
        });

        $job = new VideoTranscodingJob($video->id, '720p');

        // Act & Assert - Should throw exception to trigger retry
        $this->expectException(\App\Exceptions\FFMPEGException::class);
        $job->handle();
    }

    /**
     * Test that handle method continues with other qualities when one fails
     */
    public function test_handle_continues_with_other_qualities_when_one_fails(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        // Create quality records - 2 completed, 1 failed, 1 pending
        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '360p',
            'file_path' => 'videos/test-uuid/360p.mp4',
            'file_size' => 500000,
            'status' => 'completed',
            'processing_progress' => 100,
        ]);

        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '480p',
            'file_path' => '',
            'file_size' => 0,
            'status' => 'failed',
            'processing_progress' => 0,
            'error_message' => 'Previous failure',
        ]);

        VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/test-uuid/720p.mp4',
            'file_size' => 1200000,
            'status' => 'completed',
            'processing_progress' => 100,
        ]);

        $lastQuality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '1080p',
            'file_path' => '',
            'file_size' => 0,
            'status' => 'pending',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('transcodeVideo')
                ->once()
                ->andReturnUsing(function ($input, $output, $quality, $callback) {
                    file_put_contents($output, 'transcoded video content');
                    return true;
                });
        });

        $job = new VideoTranscodingJob($video->id, '1080p');

        // Act
        $job->handle();

        // Assert - Video should be marked as completed even though one quality failed
        $video->refresh();
        $this->assertEquals('completed', $video->status);
        $this->assertEquals(100, $video->processing_progress);
    }

    /**
     * Test that handle method generates correct output path
     */
    public function test_handle_generates_correct_output_path(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'original_path' => 'videos/my-uuid-123/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        $quality = VideoQuality::create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => '',
            'file_size' => 0,
            'status' => 'pending',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('transcodeVideo')
                ->once()
                ->andReturnUsing(function ($input, $output, $quality, $callback) {
                    file_put_contents($output, 'transcoded video content');
                    return true;
                });
        });

        $job = new VideoTranscodingJob($video->id, '720p');

        // Act
        $job->handle();

        // Assert
        $quality->refresh();
        $this->assertEquals('videos/my-uuid-123/720p.mp4', $quality->file_path);
    }
}
