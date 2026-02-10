<?php

namespace Tests\Unit;

use App\Exceptions\ExpiredSessionException;
use App\Exceptions\FFMPEGException;
use App\Exceptions\IncompleteUploadException;
use App\Exceptions\InvalidChunkException;
use App\Exceptions\InvalidSessionException;
use App\Exceptions\StorageException;
use App\Exceptions\VideoException;
use App\Exceptions\VideoInUseException;
use App\Exceptions\VideoNotFoundException;
use App\Exceptions\VideoNotReadyException;
use Illuminate\Http\Request;
use Tests\TestCase;

class VideoExceptionsTest extends TestCase
{
    /**
     * Test VideoException is the base exception
     */
    public function test_video_exception_is_base_exception(): void
    {
        $exception = new VideoException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    /**
     * Test InvalidSessionException returns 404 status code
     */
    public function test_invalid_session_exception_returns_404_status(): void
    {
        $exception = new InvalidSessionException();
        
        $response = $exception->render();
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Upload session not found', $response->getData()->message);
    }

    /**
     * Test InvalidSessionException with session ID
     */
    public function test_invalid_session_exception_includes_session_id(): void
    {
        $sessionId = 'test-session-123';
        $exception = new InvalidSessionException($sessionId);
        
        $this->assertStringContainsString($sessionId, $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    /**
     * Test ExpiredSessionException returns 410 status code
     */
    public function test_expired_session_exception_returns_410_status(): void
    {
        $exception = new ExpiredSessionException();
        
        $response = $exception->render();
        
        $this->assertEquals(410, $response->getStatusCode());
        $this->assertEquals('Upload session has expired', $response->getData()->message);
    }

    /**
     * Test ExpiredSessionException with session ID
     */
    public function test_expired_session_exception_includes_session_id(): void
    {
        $sessionId = 'expired-session-456';
        $exception = new ExpiredSessionException($sessionId);
        
        $this->assertStringContainsString($sessionId, $exception->getMessage());
        $this->assertEquals(410, $exception->getCode());
    }

    /**
     * Test IncompleteUploadException returns 400 status code
     */
    public function test_incomplete_upload_exception_returns_400_status(): void
    {
        $missingChunks = [1, 3, 5];
        $exception = new IncompleteUploadException($missingChunks);
        
        $response = $exception->render();
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Not all chunks have been received', $response->getData()->message);
        $this->assertEquals($missingChunks, $response->getData()->missing_chunks);
    }

    /**
     * Test IncompleteUploadException provides missing chunks
     */
    public function test_incomplete_upload_exception_provides_missing_chunks(): void
    {
        $missingChunks = [0, 2, 4];
        $exception = new IncompleteUploadException($missingChunks);
        
        $this->assertEquals($missingChunks, $exception->getMissingChunks());
    }

    /**
     * Test InvalidChunkException returns 400 status code
     */
    public function test_invalid_chunk_exception_returns_400_status(): void
    {
        $exception = new InvalidChunkException(10, 5);
        
        $response = $exception->render();
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Chunk number is outside valid range', $response->getData()->message);
        $this->assertEquals(10, $response->getData()->chunk_number);
        $this->assertEquals('0-4', $response->getData()->valid_range);
    }

    /**
     * Test InvalidChunkException provides chunk details
     */
    public function test_invalid_chunk_exception_provides_chunk_details(): void
    {
        $exception = new InvalidChunkException(15, 10);
        
        $this->assertEquals(15, $exception->getChunkNumber());
        $this->assertEquals(10, $exception->getMaxChunks());
        $this->assertStringContainsString('15', $exception->getMessage());
        $this->assertStringContainsString('0-9', $exception->getMessage());
    }

    /**
     * Test VideoNotFoundException returns 404 status code
     */
    public function test_video_not_found_exception_returns_404_status(): void
    {
        $exception = new VideoNotFoundException();
        
        $response = $exception->render();
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Video not found', $response->getData()->message);
    }

    /**
     * Test VideoNotFoundException with video ID
     */
    public function test_video_not_found_exception_includes_video_id(): void
    {
        $videoId = 123;
        $exception = new VideoNotFoundException($videoId);
        
        $this->assertStringContainsString((string)$videoId, $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    /**
     * Test VideoNotReadyException returns 409 status code
     */
    public function test_video_not_ready_exception_returns_409_status(): void
    {
        $exception = new VideoNotReadyException('processing', 50);
        
        $response = $exception->render();
        
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('Video is still processing and cannot be attached to lesson', $response->getData()->message);
        $this->assertEquals('processing', $response->getData()->status);
        $this->assertEquals(50, $response->getData()->progress);
    }

    /**
     * Test VideoNotReadyException provides status and progress
     */
    public function test_video_not_ready_exception_provides_status_and_progress(): void
    {
        $exception = new VideoNotReadyException('pending', 25);
        
        $this->assertEquals('pending', $exception->getStatus());
        $this->assertEquals(25, $exception->getProgress());
    }

    /**
     * Test VideoInUseException returns 409 status code
     */
    public function test_video_in_use_exception_returns_409_status(): void
    {
        $lessonIds = [1, 2, 3];
        $exception = new VideoInUseException($lessonIds);
        
        $response = $exception->render();
        
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('Video is associated with active lessons and cannot be deleted', $response->getData()->message);
        $this->assertEquals($lessonIds, $response->getData()->lesson_ids);
    }

    /**
     * Test VideoInUseException provides lesson IDs
     */
    public function test_video_in_use_exception_provides_lesson_ids(): void
    {
        $lessonIds = [5, 10, 15];
        $exception = new VideoInUseException($lessonIds);
        
        $this->assertEquals($lessonIds, $exception->getLessonIds());
        $this->assertStringContainsString('5, 10, 15', $exception->getMessage());
    }

    /**
     * Test FFMPEGException stores FFMPEG output
     */
    public function test_ffmpeg_exception_stores_ffmpeg_output(): void
    {
        $ffmpegOutput = 'FFMPEG error: Invalid codec';
        $exception = new FFMPEGException('Transcoding failed', $ffmpegOutput);
        
        $this->assertEquals('Transcoding failed', $exception->getMessage());
        $this->assertEquals($ffmpegOutput, $exception->getFfmpegOutput());
    }

    /**
     * Test FFMPEGException without FFMPEG output
     */
    public function test_ffmpeg_exception_without_output(): void
    {
        $exception = new FFMPEGException('Transcoding failed');
        
        $this->assertEquals('Transcoding failed', $exception->getMessage());
        $this->assertNull($exception->getFfmpegOutput());
    }

    /**
     * Test StorageException returns 500 status code
     */
    public function test_storage_exception_returns_500_status(): void
    {
        $exception = new StorageException('Failed to store file');
        
        $response = $exception->render();
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('Failed to store file', $response->getData()->error);
    }

    /**
     * Test StorageException stores path and operation
     */
    public function test_storage_exception_stores_path_and_operation(): void
    {
        $path = '/videos/test.mp4';
        $operation = 'delete';
        $exception = new StorageException('Storage operation failed', $path, $operation);
        
        $this->assertEquals($path, $exception->getPath());
        $this->assertEquals($operation, $exception->getOperation());
    }

    /**
     * Test all video exceptions extend VideoException
     */
    public function test_all_video_exceptions_extend_video_exception(): void
    {
        $this->assertInstanceOf(VideoException::class, new InvalidSessionException());
        $this->assertInstanceOf(VideoException::class, new ExpiredSessionException());
        $this->assertInstanceOf(VideoException::class, new IncompleteUploadException());
        $this->assertInstanceOf(VideoException::class, new InvalidChunkException(1, 10));
        $this->assertInstanceOf(VideoException::class, new VideoNotFoundException());
        $this->assertInstanceOf(VideoException::class, new VideoNotReadyException());
        $this->assertInstanceOf(VideoException::class, new VideoInUseException());
    }

    /**
     * Test all exceptions return JSON responses
     */
    public function test_all_exceptions_return_json_responses(): void
    {
        $exceptions = [
            new InvalidSessionException(),
            new ExpiredSessionException(),
            new IncompleteUploadException([1, 2]),
            new InvalidChunkException(5, 10),
            new VideoNotFoundException(),
            new VideoNotReadyException(),
            new VideoInUseException([1]),
            new StorageException('Test error'),
        ];

        foreach ($exceptions as $exception) {
            $response = $exception->render();
            $this->assertJson($response->getContent());
        }
    }
}
