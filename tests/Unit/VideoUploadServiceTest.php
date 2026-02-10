<?php

namespace Tests\Unit;

use App\Models\UploadSession;
use App\Services\VideoUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private VideoUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VideoUploadService();
    }

    /**
     * Test that initializeUpload creates a session with all required metadata
     */
    public function test_initialize_upload_creates_session_with_metadata(): void
    {
        // Arrange
        $filename = 'test-video.mp4';
        $fileSize = 1024000;
        $totalChunks = 10;

        // Act
        $session = $this->service->initializeUpload($filename, $fileSize, $totalChunks);

        // Assert
        $this->assertInstanceOf(UploadSession::class, $session);
        $this->assertNotNull($session->session_id);
        $this->assertEquals($filename, $session->filename);
        $this->assertEquals($fileSize, $session->file_size);
        $this->assertEquals($totalChunks, $session->total_chunks);
        $this->assertEquals([], $session->received_chunks);
        $this->assertEquals('pending', $session->status);
        
        // Verify it was saved to database
        $this->assertDatabaseHas('upload_sessions', [
            'session_id' => $session->session_id,
            'filename' => $filename,
            'file_size' => $fileSize,
            'total_chunks' => $totalChunks,
            'status' => 'pending',
        ]);
    }

    /**
     * Test that each session gets a unique session ID
     */
    public function test_initialize_upload_generates_unique_session_ids(): void
    {
        // Arrange
        $filename = 'test-video.mp4';
        $fileSize = 1024000;
        $totalChunks = 10;

        // Act
        $session1 = $this->service->initializeUpload($filename, $fileSize, $totalChunks);
        $session2 = $this->service->initializeUpload($filename, $fileSize, $totalChunks);
        $session3 = $this->service->initializeUpload($filename, $fileSize, $totalChunks);

        // Assert
        $this->assertNotEquals($session1->session_id, $session2->session_id);
        $this->assertNotEquals($session1->session_id, $session3->session_id);
        $this->assertNotEquals($session2->session_id, $session3->session_id);
    }

    /**
     * Test that session ID is a valid UUID format
     */
    public function test_initialize_upload_generates_valid_uuid(): void
    {
        // Arrange
        $filename = 'test-video.mp4';
        $fileSize = 1024000;
        $totalChunks = 10;

        // Act
        $session = $this->service->initializeUpload($filename, $fileSize, $totalChunks);

        // Assert - UUID v4 format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $session->session_id
        );
    }

    /**
     * Test that received_chunks is initialized as empty array
     */
    public function test_initialize_upload_initializes_empty_received_chunks(): void
    {
        // Arrange
        $filename = 'test-video.mp4';
        $fileSize = 1024000;
        $totalChunks = 10;

        // Act
        $session = $this->service->initializeUpload($filename, $fileSize, $totalChunks);

        // Assert
        $this->assertIsArray($session->received_chunks);
        $this->assertEmpty($session->received_chunks);
        $this->assertCount(0, $session->received_chunks);
    }

    /**
     * Test that storeChunk validates session exists
     */
    public function test_store_chunk_validates_session_exists(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        $invalidSessionId = 'non-existent-session-id';
        $chunkNumber = 0;
        $chunk = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);

        // Act & Assert
        $this->expectException(\App\Exceptions\InvalidSessionException::class);
        $this->service->storeChunk($invalidSessionId, $chunkNumber, $chunk);
    }

    /**
     * Test that storeChunk rejects expired session
     */
    public function test_store_chunk_rejects_expired_session(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        // Create a session that's older than 24 hours
        $session = UploadSession::create([
            'session_id' => 'test-session-id',
            'filename' => 'test.mp4',
            'file_size' => 1024000,
            'total_chunks' => 10,
            'received_chunks' => [],
            'status' => 'pending',
        ]);
        
        // Manually update the created_at timestamp to 25 hours ago
        $session->created_at = now()->subHours(25);
        $session->save();

        $chunkNumber = 0;
        $chunk = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);

        // Act & Assert
        $this->expectException(\App\Exceptions\ExpiredSessionException::class);
        $this->service->storeChunk($session->session_id, $chunkNumber, $chunk);
    }

    /**
     * Test that storeChunk rejects chunk number below valid range
     */
    public function test_store_chunk_rejects_negative_chunk_number(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 10);
        $chunkNumber = -1;
        $chunk = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);

        // Act & Assert
        $this->expectException(\App\Exceptions\InvalidChunkException::class);
        $this->service->storeChunk($session->session_id, $chunkNumber, $chunk);
    }

    /**
     * Test that storeChunk rejects chunk number above valid range
     */
    public function test_store_chunk_rejects_chunk_number_above_range(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 10);
        $chunkNumber = 10; // Valid range is 0-9
        $chunk = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);

        // Act & Assert
        $this->expectException(\App\Exceptions\InvalidChunkException::class);
        $this->service->storeChunk($session->session_id, $chunkNumber, $chunk);
    }

    /**
     * Test that storeChunk stores chunk in correct location
     */
    public function test_store_chunk_stores_file_in_correct_location(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 10);
        $chunkNumber = 0;
        $chunk = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);

        // Act
        $result = $this->service->storeChunk($session->session_id, $chunkNumber, $chunk);

        // Assert
        $this->assertTrue($result);
        $expectedPath = "temp/uploads/{$session->session_id}/chunk_{$chunkNumber}";
        \Illuminate\Support\Facades\Storage::assertExists($expectedPath);
    }

    /**
     * Test that storeChunk marks chunk as received
     */
    public function test_store_chunk_marks_chunk_as_received(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 10);
        $chunkNumber = 0;
        $chunk = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);

        // Act
        $this->service->storeChunk($session->session_id, $chunkNumber, $chunk);

        // Assert
        $session->refresh();
        $this->assertContains($chunkNumber, $session->received_chunks);
    }

    /**
     * Test that storeChunk allows uploading multiple chunks
     */
    public function test_store_chunk_allows_multiple_chunks(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 10);
        $chunk0 = \Illuminate\Http\UploadedFile::fake()->create('chunk0.bin', 1024);
        $chunk1 = \Illuminate\Http\UploadedFile::fake()->create('chunk1.bin', 1024);
        $chunk2 = \Illuminate\Http\UploadedFile::fake()->create('chunk2.bin', 1024);

        // Act
        $this->service->storeChunk($session->session_id, 0, $chunk0);
        $this->service->storeChunk($session->session_id, 1, $chunk1);
        $this->service->storeChunk($session->session_id, 2, $chunk2);

        // Assert
        $session->refresh();
        $this->assertContains(0, $session->received_chunks);
        $this->assertContains(1, $session->received_chunks);
        $this->assertContains(2, $session->received_chunks);
        $this->assertCount(3, $session->received_chunks);
        
        // Verify all files exist
        \Illuminate\Support\Facades\Storage::assertExists("temp/uploads/{$session->session_id}/chunk_0");
        \Illuminate\Support\Facades\Storage::assertExists("temp/uploads/{$session->session_id}/chunk_1");
        \Illuminate\Support\Facades\Storage::assertExists("temp/uploads/{$session->session_id}/chunk_2");
    }

    /**
     * Test that storeChunk allows retrying the same chunk (idempotent)
     */
    public function test_store_chunk_allows_retry_of_same_chunk(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 10);
        $chunkNumber = 0;
        $chunk1 = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);
        $chunk2 = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);

        // Act - Upload same chunk twice
        $result1 = $this->service->storeChunk($session->session_id, $chunkNumber, $chunk1);
        $result2 = $this->service->storeChunk($session->session_id, $chunkNumber, $chunk2);

        // Assert
        $this->assertTrue($result1);
        $this->assertTrue($result2);
        
        $session->refresh();
        $this->assertContains($chunkNumber, $session->received_chunks);
        $this->assertCount(1, $session->received_chunks); // Should only be recorded once
    }

    /**
     * Test that storeChunk validates chunk number at boundary (0)
     */
    public function test_store_chunk_accepts_chunk_zero(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 10);
        $chunkNumber = 0;
        $chunk = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);

        // Act
        $result = $this->service->storeChunk($session->session_id, $chunkNumber, $chunk);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that storeChunk validates chunk number at boundary (total_chunks - 1)
     */
    public function test_store_chunk_accepts_last_chunk(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $totalChunks = 10;
        $session = $this->service->initializeUpload('test.mp4', 1024000, $totalChunks);
        $chunkNumber = $totalChunks - 1; // 9
        $chunk = \Illuminate\Http\UploadedFile::fake()->create('chunk.bin', 1024);

        // Act
        $result = $this->service->storeChunk($session->session_id, $chunkNumber, $chunk);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that completeUpload validates session exists
     */
    public function test_complete_upload_validates_session_exists(): void
    {
        // Arrange
        $invalidSessionId = 'non-existent-session-id';

        // Act & Assert
        $this->expectException(\App\Exceptions\InvalidSessionException::class);
        $this->service->completeUpload($invalidSessionId);
    }

    /**
     * Test that completeUpload throws exception when chunks are missing
     */
    public function test_complete_upload_fails_with_missing_chunks(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        \Illuminate\Support\Facades\Queue::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 5);
        
        // Upload only 3 out of 5 chunks
        $chunk0 = \Illuminate\Http\UploadedFile::fake()->create('chunk0.bin', 1024);
        $chunk1 = \Illuminate\Http\UploadedFile::fake()->create('chunk1.bin', 1024);
        $chunk3 = \Illuminate\Http\UploadedFile::fake()->create('chunk3.bin', 1024);
        
        $this->service->storeChunk($session->session_id, 0, $chunk0);
        $this->service->storeChunk($session->session_id, 1, $chunk1);
        $this->service->storeChunk($session->session_id, 3, $chunk3);

        // Act & Assert
        try {
            $this->service->completeUpload($session->session_id);
            $this->fail('Expected IncompleteUploadException was not thrown');
        } catch (\App\Exceptions\IncompleteUploadException $e) {
            // Assert missing chunks are reported
            $missingChunks = $e->getMissingChunks();
            $this->assertContains(2, $missingChunks);
            $this->assertContains(4, $missingChunks);
            $this->assertCount(2, $missingChunks);
        }
    }

    /**
     * Test that completeUpload dispatches ChunkAssemblyJob when all chunks received
     */
    public function test_complete_upload_dispatches_assembly_job(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        \Illuminate\Support\Facades\Queue::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 3);
        
        // Upload all chunks
        for ($i = 0; $i < 3; $i++) {
            $chunk = \Illuminate\Http\UploadedFile::fake()->create("chunk{$i}.bin", 1024);
            $this->service->storeChunk($session->session_id, $i, $chunk);
        }

        // Act
        $video = $this->service->completeUpload($session->session_id);

        // Assert
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ChunkAssemblyJob::class, function ($job) use ($session) {
            return $job->sessionId === $session->session_id;
        });
    }

    /**
     * Test that completeUpload returns placeholder Video model
     */
    public function test_complete_upload_returns_placeholder_video(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        \Illuminate\Support\Facades\Queue::fake();
        
        $filename = 'test-video.mp4';
        $fileSize = 1024000;
        $session = $this->service->initializeUpload($filename, $fileSize, 2);
        
        // Upload all chunks
        for ($i = 0; $i < 2; $i++) {
            $chunk = \Illuminate\Http\UploadedFile::fake()->create("chunk{$i}.bin", 1024);
            $this->service->storeChunk($session->session_id, $i, $chunk);
        }

        // Act
        $video = $this->service->completeUpload($session->session_id);

        // Assert
        $this->assertInstanceOf(\App\Models\Video::class, $video);
        $this->assertEquals($filename, $video->original_filename);
        $this->assertEquals($filename, $video->display_name);
        $this->assertEquals($fileSize, $video->file_size);
        $this->assertEquals('pending', $video->status);
        $this->assertEquals(0, $video->processing_progress);
    }

    /**
     * Test that getUploadProgress validates session exists
     */
    public function test_get_upload_progress_validates_session_exists(): void
    {
        // Arrange
        $invalidSessionId = 'non-existent-session-id';

        // Act & Assert
        $this->expectException(\App\Exceptions\InvalidSessionException::class);
        $this->service->getUploadProgress($invalidSessionId);
    }

    /**
     * Test that getUploadProgress returns correct progress for empty session
     */
    public function test_get_upload_progress_returns_zero_for_new_session(): void
    {
        // Arrange
        $session = $this->service->initializeUpload('test.mp4', 1024000, 10);

        // Act
        $progress = $this->service->getUploadProgress($session->session_id);

        // Assert
        $this->assertIsArray($progress);
        $this->assertArrayHasKey('received', $progress);
        $this->assertArrayHasKey('total', $progress);
        $this->assertArrayHasKey('percentage', $progress);
        $this->assertEquals(0, $progress['received']);
        $this->assertEquals(10, $progress['total']);
        $this->assertEquals(0, $progress['percentage']);
    }

    /**
     * Test that getUploadProgress returns correct progress for partial upload
     */
    public function test_get_upload_progress_returns_correct_partial_progress(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 10);
        
        // Upload 3 out of 10 chunks
        for ($i = 0; $i < 3; $i++) {
            $chunk = \Illuminate\Http\UploadedFile::fake()->create("chunk{$i}.bin", 1024);
            $this->service->storeChunk($session->session_id, $i, $chunk);
        }

        // Act
        $progress = $this->service->getUploadProgress($session->session_id);

        // Assert
        $this->assertEquals(3, $progress['received']);
        $this->assertEquals(10, $progress['total']);
        $this->assertEquals(30.0, $progress['percentage']);
    }

    /**
     * Test that getUploadProgress returns 100% for complete upload
     */
    public function test_get_upload_progress_returns_100_percent_when_complete(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 5);
        
        // Upload all chunks
        for ($i = 0; $i < 5; $i++) {
            $chunk = \Illuminate\Http\UploadedFile::fake()->create("chunk{$i}.bin", 1024);
            $this->service->storeChunk($session->session_id, $i, $chunk);
        }

        // Act
        $progress = $this->service->getUploadProgress($session->session_id);

        // Assert
        $this->assertEquals(5, $progress['received']);
        $this->assertEquals(5, $progress['total']);
        $this->assertEquals(100.0, $progress['percentage']);
    }

    /**
     * Test that getUploadProgress calculates percentage correctly with decimals
     */
    public function test_get_upload_progress_calculates_percentage_with_decimals(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 3);
        
        // Upload 1 out of 3 chunks (33.33%)
        $chunk = \Illuminate\Http\UploadedFile::fake()->create("chunk0.bin", 1024);
        $this->service->storeChunk($session->session_id, 0, $chunk);

        // Act
        $progress = $this->service->getUploadProgress($session->session_id);

        // Assert
        $this->assertEquals(1, $progress['received']);
        $this->assertEquals(3, $progress['total']);
        $this->assertEquals(33.33, $progress['percentage']);
    }

    /**
     * Test that cancelUpload validates session exists
     */
    public function test_cancel_upload_validates_session_exists(): void
    {
        // Arrange
        $invalidSessionId = 'non-existent-session-id';

        // Act & Assert
        $this->expectException(\App\Exceptions\InvalidSessionException::class);
        $this->service->cancelUpload($invalidSessionId);
    }

    /**
     * Test that cancelUpload deletes chunk files
     */
    public function test_cancel_upload_deletes_chunk_files(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 3);
        
        // Upload some chunks
        for ($i = 0; $i < 3; $i++) {
            $chunk = \Illuminate\Http\UploadedFile::fake()->create("chunk{$i}.bin", 1024);
            $this->service->storeChunk($session->session_id, $i, $chunk);
        }
        
        // Verify chunks exist
        \Illuminate\Support\Facades\Storage::assertExists("temp/uploads/{$session->session_id}/chunk_0");
        \Illuminate\Support\Facades\Storage::assertExists("temp/uploads/{$session->session_id}/chunk_1");
        \Illuminate\Support\Facades\Storage::assertExists("temp/uploads/{$session->session_id}/chunk_2");

        // Act
        $result = $this->service->cancelUpload($session->session_id);

        // Assert
        $this->assertTrue($result);
        \Illuminate\Support\Facades\Storage::assertDirectoryEmpty("temp/uploads");
    }

    /**
     * Test that cancelUpload deletes session record
     */
    public function test_cancel_upload_deletes_session_record(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 3);
        $sessionId = $session->session_id;
        
        // Verify session exists
        $this->assertDatabaseHas('upload_sessions', [
            'session_id' => $sessionId,
        ]);

        // Act
        $result = $this->service->cancelUpload($sessionId);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('upload_sessions', [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Test that cancelUpload works even if no chunks were uploaded
     */
    public function test_cancel_upload_works_with_no_chunks(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 3);
        $sessionId = $session->session_id;

        // Act - Cancel without uploading any chunks
        $result = $this->service->cancelUpload($sessionId);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('upload_sessions', [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Test that cancelUpload returns success status
     */
    public function test_cancel_upload_returns_success_status(): void
    {
        // Arrange
        \Illuminate\Support\Facades\Storage::fake();
        
        $session = $this->service->initializeUpload('test.mp4', 1024000, 3);

        // Act
        $result = $this->service->cancelUpload($session->session_id);

        // Assert
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }
}
