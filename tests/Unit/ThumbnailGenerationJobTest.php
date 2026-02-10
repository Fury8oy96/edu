<?php

namespace Tests\Unit;

use App\Jobs\ThumbnailGenerationJob;
use App\Models\Video;
use App\Services\FFMPEGService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThumbnailGenerationJobTest extends TestCase
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
        $job = new ThumbnailGenerationJob(999);

        // Act - Should not throw exception, just log warning
        $job->handle();

        // Assert - No exception should be thrown
        $this->assertTrue(true);
    }

    /**
     * Test that handle method generates thumbnail successfully
     */
    public function test_handle_generates_thumbnail_successfully(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'duration' => 120.5,
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('generateThumbnail')
                ->once()
                ->with(
                    \Mockery::any(),
                    \Mockery::any(),
                    5 // Default time for videos longer than 5 seconds
                )
                ->andReturnUsing(function ($input, $output, $time) {
                    // Create the thumbnail file
                    file_put_contents($output, 'fake thumbnail content');
                    return true;
                });
        });

        $job = new ThumbnailGenerationJob($video->id);

        // Act
        $job->handle();

        // Assert
        $video->refresh();
        $this->assertNotNull($video->thumbnail_path);
        $this->assertEquals('videos/test-uuid/thumbnail.jpg', $video->thumbnail_path);
    }

    /**
     * Test that handle method uses 1 second for short videos
     */
    public function test_handle_uses_1_second_for_short_videos(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'short.mp4',
            'display_name' => 'short.mp4',
            'file_size' => 512000,
            'duration' => 3.5, // Short video (less than 5 seconds)
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('generateThumbnail')
                ->once()
                ->with(
                    \Mockery::any(),
                    \Mockery::any(),
                    1 // Should use 1 second for short videos
                )
                ->andReturnUsing(function ($input, $output, $time) {
                    file_put_contents($output, 'fake thumbnail content');
                    return true;
                });
        });

        $job = new ThumbnailGenerationJob($video->id);

        // Act
        $job->handle();

        // Assert
        $video->refresh();
        $this->assertNotNull($video->thumbnail_path);
    }

    /**
     * Test that handle method does not fail job on FFMPEG error
     */
    public function test_handle_does_not_fail_job_on_ffmpeg_error(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'duration' => 120.5,
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService to throw exception
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('generateThumbnail')
                ->once()
                ->andThrow(new \App\Exceptions\FFMPEGException('Thumbnail generation failed', 'FFMPEG error'));
        });

        $job = new ThumbnailGenerationJob($video->id);

        // Act - Should not throw exception
        $job->handle();

        // Assert - Video should not have thumbnail but job should not fail
        $video->refresh();
        $this->assertNull($video->thumbnail_path);
    }

    /**
     * Test that handle method does not fail job on general error
     */
    public function test_handle_does_not_fail_job_on_general_error(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'duration' => 120.5,
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService to throw general exception
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('generateThumbnail')
                ->once()
                ->andThrow(new \RuntimeException('Unexpected error'));
        });

        $job = new ThumbnailGenerationJob($video->id);

        // Act - Should not throw exception
        $job->handle();

        // Assert - Video should not have thumbnail but job should not fail
        $video->refresh();
        $this->assertNull($video->thumbnail_path);
    }

    /**
     * Test that handle method generates correct thumbnail path
     */
    public function test_handle_generates_correct_thumbnail_path(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'duration' => 120.5,
            'original_path' => 'videos/my-special-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('generateThumbnail')
                ->once()
                ->andReturnUsing(function ($input, $output, $time) {
                    file_put_contents($output, 'fake thumbnail content');
                    return true;
                });
        });

        $job = new ThumbnailGenerationJob($video->id);

        // Act
        $job->handle();

        // Assert
        $video->refresh();
        $this->assertEquals('videos/my-special-uuid/thumbnail.jpg', $video->thumbnail_path);
    }

    /**
     * Test that handle method uses default 5 seconds when duration is null
     */
    public function test_handle_uses_default_5_seconds_when_duration_is_null(): void
    {
        // Arrange
        $video = Video::create([
            'original_filename' => 'test.mp4',
            'display_name' => 'test.mp4',
            'file_size' => 1024000,
            'duration' => null, // No duration set
            'original_path' => 'videos/test-uuid/original.mp4',
            'status' => 'processing',
            'processing_progress' => 0,
        ]);

        // Create a fake video file
        Storage::disk('local')->put($video->original_path, 'fake video content');

        // Mock FFMPEGService
        $this->mock(FFMPEGService::class, function ($mock) {
            $mock->shouldReceive('generateThumbnail')
                ->once()
                ->with(
                    \Mockery::any(),
                    \Mockery::any(),
                    5 // Should use default 5 seconds
                )
                ->andReturnUsing(function ($input, $output, $time) {
                    file_put_contents($output, 'fake thumbnail content');
                    return true;
                });
        });

        $job = new ThumbnailGenerationJob($video->id);

        // Act
        $job->handle();

        // Assert
        $video->refresh();
        $this->assertNotNull($video->thumbnail_path);
    }
}
