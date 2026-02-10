<?php

namespace Tests\Unit;

use App\Exceptions\VideoInUseException;
use App\Exceptions\VideoNotFoundException;
use App\Models\Lessons;
use App\Models\Video;
use App\Models\VideoQuality;
use App\Services\VideoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoServiceTest extends TestCase
{
    use RefreshDatabase;

    private VideoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VideoService();
        Storage::fake('videos');
    }

    /**
     * Test that listVideos returns all videos without filters
     */
    public function test_list_videos_returns_all_videos(): void
    {
        // Arrange
        Video::factory()->count(5)->create();

        // Act
        $result = $this->service->listVideos([], 15);

        // Assert
        $this->assertEquals(5, $result->total());
    }

    /**
     * Test that listVideos filters by status correctly
     */
    public function test_list_videos_filters_by_status(): void
    {
        // Arrange
        Video::factory()->count(3)->create(['status' => 'completed']);
        Video::factory()->count(2)->create(['status' => 'processing']);
        Video::factory()->count(1)->create(['status' => 'failed']);

        // Act
        $result = $this->service->listVideos(['status' => 'completed'], 15);

        // Assert
        $this->assertEquals(3, $result->total());
        foreach ($result->items() as $video) {
            $this->assertEquals('completed', $video->status);
        }
    }

    /**
     * Test that listVideos searches by filename
     */
    public function test_list_videos_searches_by_filename(): void
    {
        // Arrange
        Video::factory()->create(['original_filename' => 'introduction-video.mp4']);
        Video::factory()->create(['original_filename' => 'advanced-tutorial.mp4']);
        Video::factory()->create(['display_name' => 'Introduction to Laravel']);

        // Act
        $result = $this->service->listVideos(['search' => 'introduction'], 15);

        // Assert
        $this->assertEquals(2, $result->total());
    }

    /**
     * Test that listVideos filters by lesson_id
     */
    public function test_list_videos_filters_by_lesson_id(): void
    {
        // Arrange
        $lesson1 = Lessons::factory()->create();
        $lesson2 = Lessons::factory()->create();
        
        $video1 = Video::factory()->create();
        $video2 = Video::factory()->create();
        $video3 = Video::factory()->create();
        
        $video1->lessons()->attach($lesson1->id);
        $video2->lessons()->attach($lesson1->id);
        $video3->lessons()->attach($lesson2->id);

        // Act
        $result = $this->service->listVideos(['lesson_id' => $lesson1->id], 15);

        // Assert
        $this->assertEquals(2, $result->total());
    }

    /**
     * Test that listVideos includes lesson count
     */
    public function test_list_videos_includes_lesson_count(): void
    {
        // Arrange
        $lesson1 = Lessons::factory()->create();
        $lesson2 = Lessons::factory()->create();
        
        $video = Video::factory()->create();
        $video->lessons()->attach([$lesson1->id, $lesson2->id]);

        // Act
        $result = $this->service->listVideos([], 15);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals(2, $result->items()[0]->lessons_count);
    }

    /**
     * Test that listVideos respects pagination
     */
    public function test_list_videos_respects_pagination(): void
    {
        // Arrange
        Video::factory()->count(25)->create();

        // Act
        $result = $this->service->listVideos([], 10);

        // Assert
        $this->assertEquals(25, $result->total());
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(10, count($result->items()));
    }

    /**
     * Test that getVideo retrieves video by ID
     */
    public function test_get_video_retrieves_by_id(): void
    {
        // Arrange
        $video = Video::factory()->create();

        // Act
        $result = $this->service->getVideo($video->id);

        // Assert
        $this->assertInstanceOf(Video::class, $result);
        $this->assertEquals($video->id, $result->id);
    }

    /**
     * Test that getVideo throws exception for non-existent video
     */
    public function test_get_video_throws_exception_for_non_existent_video(): void
    {
        // Assert
        $this->expectException(VideoNotFoundException::class);

        // Act
        $this->service->getVideo(999);
    }

    /**
     * Test that updateVideo modifies display_name
     */
    public function test_update_video_modifies_display_name(): void
    {
        // Arrange
        $video = Video::factory()->create(['display_name' => 'Old Name']);

        // Act
        $result = $this->service->updateVideo($video->id, ['display_name' => 'New Name']);

        // Assert
        $this->assertEquals('New Name', $result->display_name);
        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'display_name' => 'New Name'
        ]);
    }

    /**
     * Test that updateVideo throws exception for empty display_name
     */
    public function test_update_video_rejects_empty_display_name(): void
    {
        // Arrange
        $video = Video::factory()->create(['display_name' => 'Valid Name']);

        // Assert
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $this->service->updateVideo($video->id, ['display_name' => '   ']);
    }

    /**
     * Test that updateVideo prevents technical metadata changes
     */
    public function test_update_video_prevents_technical_metadata_changes(): void
    {
        // Arrange
        $video = Video::factory()->create([
            'display_name' => 'Original Name',
            'duration' => 120.50,
            'resolution' => '1920x1080',
            'codec' => 'h264',
            'format' => 'mp4'
        ]);

        // Act
        $result = $this->service->updateVideo($video->id, [
            'display_name' => 'New Name',
            'duration' => 999.99,
            'resolution' => '640x480',
            'codec' => 'vp9',
            'format' => 'webm'
        ]);

        // Assert
        $this->assertEquals('New Name', $result->display_name);
        $this->assertEquals(120.50, $result->duration);
        $this->assertEquals('1920x1080', $result->resolution);
        $this->assertEquals('h264', $result->codec);
        $this->assertEquals('mp4', $result->format);
    }

    /**
     * Test that updateVideo throws exception for non-existent video
     */
    public function test_update_video_throws_exception_for_non_existent_video(): void
    {
        // Assert
        $this->expectException(VideoNotFoundException::class);

        // Act
        $this->service->updateVideo(999, ['display_name' => 'New Name']);
    }

    /**
     * Test that updateVideo updates timestamp
     */
    public function test_update_video_updates_timestamp(): void
    {
        // Arrange
        $video = Video::factory()->create(['display_name' => 'Old Name']);
        $originalUpdatedAt = $video->updated_at;
        
        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Act
        $result = $this->service->updateVideo($video->id, ['display_name' => 'New Name']);

        // Assert
        $this->assertTrue($result->updated_at->isAfter($originalUpdatedAt));
    }

    /**
     * Test that deleteVideo removes video without active lessons
     */
    public function test_delete_video_removes_video_without_active_lessons(): void
    {
        // Arrange
        $video = Video::factory()->create([
            'original_path' => 'videos/1/1/1/original.mp4',
            'thumbnail_path' => 'videos/1/1/1/thumbnail.jpg'
        ]);
        
        VideoQuality::factory()->create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/1/1/1/720p.mp4'
        ]);

        // Create fake files
        Storage::disk('videos')->put($video->original_path, 'content');
        Storage::disk('videos')->put($video->thumbnail_path, 'content');
        Storage::disk('videos')->put('videos/1/1/1/720p.mp4', 'content');

        // Act
        $result = $this->service->deleteVideo($video->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
        $this->assertDatabaseMissing('video_qualities', ['video_id' => $video->id]);
    }

    /**
     * Test that deleteVideo throws exception for video with active lessons
     */
    public function test_delete_video_prevents_deletion_with_active_lessons(): void
    {
        // Arrange
        $video = Video::factory()->create();
        $lesson = Lessons::factory()->create();
        $video->lessons()->attach($lesson->id);

        // Assert
        $this->expectException(VideoInUseException::class);

        // Act
        $this->service->deleteVideo($video->id);
    }

    /**
     * Test that deleteVideo throws exception for non-existent video
     */
    public function test_delete_video_throws_exception_for_non_existent_video(): void
    {
        // Assert
        $this->expectException(VideoNotFoundException::class);

        // Act
        $this->service->deleteVideo(999);
    }

    /**
     * Test that deleteVideo deletes all associated files
     */
    public function test_delete_video_deletes_all_associated_files(): void
    {
        // Arrange
        $video = Video::factory()->create([
            'original_path' => 'videos/1/1/1/original.mp4',
            'thumbnail_path' => 'videos/1/1/1/thumbnail.jpg'
        ]);
        
        VideoQuality::factory()->create([
            'video_id' => $video->id,
            'quality' => '360p',
            'file_path' => 'videos/1/1/1/360p.mp4'
        ]);
        
        VideoQuality::factory()->create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/1/1/1/720p.mp4'
        ]);

        // Create fake files
        Storage::disk('videos')->put($video->original_path, 'content');
        Storage::disk('videos')->put($video->thumbnail_path, 'content');
        Storage::disk('videos')->put('videos/1/1/1/360p.mp4', 'content');
        Storage::disk('videos')->put('videos/1/1/1/720p.mp4', 'content');

        // Act
        $this->service->deleteVideo($video->id);

        // Assert
        Storage::disk('videos')->assertMissing($video->original_path);
        Storage::disk('videos')->assertMissing($video->thumbnail_path);
        Storage::disk('videos')->assertMissing('videos/1/1/1/360p.mp4');
        Storage::disk('videos')->assertMissing('videos/1/1/1/720p.mp4');
    }

    /**
     * Test that bulkDeleteVideos validates all video IDs
     */
    public function test_bulk_delete_validates_all_video_ids(): void
    {
        // Arrange
        $video1 = Video::factory()->create();
        $video2 = Video::factory()->create();

        // Act
        $result = $this->service->bulkDeleteVideos([$video1->id, $video2->id, 999]);

        // Assert
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertCount(1, array_filter($result['failed'], fn($f) => $f['id'] === 999));
    }

    /**
     * Test that bulkDeleteVideos skips videos with active lessons
     */
    public function test_bulk_delete_skips_videos_with_active_lessons(): void
    {
        // Arrange
        $video1 = Video::factory()->create();
        $video2 = Video::factory()->create();
        $lesson = Lessons::factory()->create();
        $video2->lessons()->attach($lesson->id);

        // Act
        $result = $this->service->bulkDeleteVideos([$video1->id, $video2->id]);

        // Assert
        $this->assertContains($video1->id, $result['success']);
        $this->assertCount(1, array_filter($result['failed'], fn($f) => $f['id'] === $video2->id));
        $this->assertDatabaseMissing('videos', ['id' => $video1->id]);
        $this->assertDatabaseHas('videos', ['id' => $video2->id]);
    }

    /**
     * Test that bulkDeleteVideos returns summary with success and failed arrays
     */
    public function test_bulk_delete_returns_summary(): void
    {
        // Arrange
        $video1 = Video::factory()->create();
        $video2 = Video::factory()->create();
        $video3 = Video::factory()->create();
        $lesson = Lessons::factory()->create();
        $video3->lessons()->attach($lesson->id);

        // Act
        $result = $this->service->bulkDeleteVideos([$video1->id, $video2->id, $video3->id, 999]);

        // Assert
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertCount(2, $result['success']);
        $this->assertCount(2, $result['failed']);
    }

    /**
     * Test that getProcessingProgress returns correct status and progress
     */
    public function test_get_processing_progress_returns_correct_data(): void
    {
        // Arrange
        $video = Video::factory()->create(['status' => 'processing']);
        
        VideoQuality::factory()->create([
            'video_id' => $video->id,
            'quality' => '360p',
            'status' => 'completed'
        ]);
        
        VideoQuality::factory()->create([
            'video_id' => $video->id,
            'quality' => '720p',
            'status' => 'completed'
        ]);
        
        VideoQuality::factory()->create([
            'video_id' => $video->id,
            'quality' => '1080p',
            'status' => 'processing'
        ]);

        // Act
        $result = $this->service->getProcessingProgress($video->id);

        // Assert
        $this->assertEquals('processing', $result['status']);
        $this->assertEquals(50.0, $result['progress']); // 2 out of 4 qualities completed
        $this->assertCount(2, $result['completed_qualities']);
        $this->assertContains('360p', $result['completed_qualities']);
        $this->assertContains('720p', $result['completed_qualities']);
    }

    /**
     * Test that getProcessingProgress throws exception for non-existent video
     */
    public function test_get_processing_progress_throws_exception_for_non_existent_video(): void
    {
        // Assert
        $this->expectException(VideoNotFoundException::class);

        // Act
        $this->service->getProcessingProgress(999);
    }

    /**
     * Test that getVideoUrls returns URLs for completed qualities only
     */
    public function test_get_video_urls_returns_completed_qualities_only(): void
    {
        // Arrange
        $video = Video::factory()->create([
            'thumbnail_path' => 'videos/1/1/1/thumbnail.jpg'
        ]);
        
        VideoQuality::factory()->create([
            'video_id' => $video->id,
            'quality' => '360p',
            'file_path' => 'videos/1/1/1/360p.mp4',
            'status' => 'completed'
        ]);
        
        VideoQuality::factory()->create([
            'video_id' => $video->id,
            'quality' => '720p',
            'file_path' => 'videos/1/1/1/720p.mp4',
            'status' => 'processing'
        ]);

        // Create fake files
        Storage::disk('videos')->put('videos/1/1/1/360p.mp4', 'content');
        Storage::disk('videos')->put('videos/1/1/1/thumbnail.jpg', 'content');

        // Act
        $result = $this->service->getVideoUrls($video->id);

        // Assert
        $this->assertArrayHasKey('qualities', $result);
        $this->assertArrayHasKey('thumbnail', $result);
        $this->assertArrayHasKey('360p', $result['qualities']);
        $this->assertArrayNotHasKey('720p', $result['qualities']);
        $this->assertNotNull($result['thumbnail']);
    }

    /**
     * Test that getVideoUrls includes thumbnail URL
     */
    public function test_get_video_urls_includes_thumbnail(): void
    {
        // Arrange
        $video = Video::factory()->create([
            'thumbnail_path' => 'videos/1/1/1/thumbnail.jpg'
        ]);
        
        Storage::disk('videos')->put('videos/1/1/1/thumbnail.jpg', 'content');

        // Act
        $result = $this->service->getVideoUrls($video->id);

        // Assert
        $this->assertNotNull($result['thumbnail']);
    }

    /**
     * Test that getVideoUrls returns null thumbnail when not available
     */
    public function test_get_video_urls_returns_null_thumbnail_when_not_available(): void
    {
        // Arrange
        $video = Video::factory()->create(['thumbnail_path' => null]);

        // Act
        $result = $this->service->getVideoUrls($video->id);

        // Assert
        $this->assertNull($result['thumbnail']);
    }

    /**
     * Test that getVideoUrls throws exception for non-existent video
     */
    public function test_get_video_urls_throws_exception_for_non_existent_video(): void
    {
        // Assert
        $this->expectException(VideoNotFoundException::class);

        // Act
        $this->service->getVideoUrls(999);
    }
}
