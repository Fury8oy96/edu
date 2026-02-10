<?php

namespace App\Services;

use App\Models\UploadSession;
use Illuminate\Support\Str;

class VideoUploadService
{
    /**
     * Initialize a new chunked upload session
     * 
     * @param string $filename Original filename
     * @param int $fileSize Total file size in bytes
     * @param int $totalChunks Expected number of chunks
     * @return UploadSession
     */
    public function initializeUpload(
        string $filename, 
        int $fileSize, 
        int $totalChunks
    ): UploadSession {
        // Generate unique session ID using UUID
        $sessionId = (string) Str::uuid();

        // Create UploadSession record with provided metadata
        $session = UploadSession::create([
            'session_id' => $sessionId,
            'filename' => $filename,
            'file_size' => $fileSize,
            'total_chunks' => $totalChunks,
            'received_chunks' => [],
            'status' => 'pending',
        ]);

        // Return created session
        return $session;
    }

    /**
     * Store an uploaded chunk
     * 
     * @param string $sessionId Upload session identifier
     * @param int $chunkNumber Chunk sequence number (0-indexed)
     * @param \Illuminate\Http\UploadedFile $chunk The uploaded chunk file
     * @return bool Success status
     * @throws \App\Exceptions\InvalidSessionException
     * @throws \App\Exceptions\ExpiredSessionException
     * @throws \App\Exceptions\InvalidChunkException
     */
    public function storeChunk(
        string $sessionId, 
        int $chunkNumber, 
        $chunk
    ): bool {
        // Validate session exists
        $session = UploadSession::where('session_id', $sessionId)->first();
        
        if (!$session) {
            throw new \App\Exceptions\InvalidSessionException($sessionId);
        }
        
        // Validate session is not expired
        if ($session->isExpired()) {
            throw new \App\Exceptions\ExpiredSessionException($sessionId);
        }
        
        // Validate chunk number is within range (0 to total_chunks-1)
        if ($chunkNumber < 0 || $chunkNumber >= $session->total_chunks) {
            throw new \App\Exceptions\InvalidChunkException($chunkNumber, $session->total_chunks);
        }
        
        // Store chunk file in temporary storage: storage/app/temp/uploads/{session_id}/chunk_{number}
        $chunkPath = "temp/uploads/{$sessionId}/chunk_{$chunkNumber}";
        \Illuminate\Support\Facades\Storage::put($chunkPath, $chunk->get());
        
        // Mark chunk as received in session using markChunkReceived()
        $session->markChunkReceived($chunkNumber);
        
        return true;
    }

    /**
     * Complete upload and trigger assembly
     * 
     * @param string $sessionId Upload session identifier
     * @return \App\Models\Video The created video record (placeholder)
     * @throws \App\Exceptions\IncompleteUploadException
     * @throws \App\Exceptions\InvalidSessionException
     */
    public function completeUpload(string $sessionId): \App\Models\Video
    {
        // Validate session exists
        $session = UploadSession::where('session_id', $sessionId)->first();
        
        if (!$session) {
            throw new \App\Exceptions\InvalidSessionException($sessionId);
        }
        
        // Validate all chunks received
        if (!$session->isComplete()) {
            // Calculate missing chunks
            $receivedChunks = $session->received_chunks;
            $expectedChunks = range(0, $session->total_chunks - 1);
            $missingChunks = array_diff($expectedChunks, $receivedChunks);
            
            throw new \App\Exceptions\IncompleteUploadException(array_values($missingChunks));
        }
        
        // Dispatch ChunkAssemblyJob with session ID
        \App\Jobs\ChunkAssemblyJob::dispatch($sessionId);
        
        // Return placeholder Video model (will be created by job)
        // Create a temporary placeholder video record
        $video = new \App\Models\Video([
            'original_filename' => $session->filename,
            'display_name' => $session->filename,
            'file_size' => $session->file_size,
            'status' => 'pending',
            'processing_progress' => 0,
        ]);
        
        return $video;
    }

    /**
     * Get upload progress
     * 
     * @param string $sessionId Upload session identifier
     * @return array ['received' => int, 'total' => int, 'percentage' => float]
     * @throws \App\Exceptions\InvalidSessionException
     */
    public function getUploadProgress(string $sessionId): array
    {
        // Validate session exists
        $session = UploadSession::where('session_id', $sessionId)->first();
        
        if (!$session) {
            throw new \App\Exceptions\InvalidSessionException($sessionId);
        }
        
        // Return array with received chunk count, total chunks, and percentage
        $received = count($session->received_chunks);
        $total = $session->total_chunks;
        $percentage = $total > 0 ? round(($received / $total) * 100, 2) : 0;
        
        return [
            'received' => $received,
            'total' => $total,
            'percentage' => $percentage,
        ];
    }

    /**
     * Cancel an upload session
     * 
     * @param string $sessionId Upload session identifier
     * @return bool Success status
     * @throws \App\Exceptions\InvalidSessionException
     */
    public function cancelUpload(string $sessionId): bool
    {
        // Validate session exists
        $session = UploadSession::where('session_id', $sessionId)->first();
        
        if (!$session) {
            throw new \App\Exceptions\InvalidSessionException($sessionId);
        }
        
        // Delete all chunk files for session
        $chunkDirectory = "temp/uploads/{$sessionId}";
        
        // Check if directory exists and delete all files
        if (\Illuminate\Support\Facades\Storage::exists($chunkDirectory)) {
            \Illuminate\Support\Facades\Storage::deleteDirectory($chunkDirectory);
        }
        
        // Delete session record
        $session->delete();
        
        // Return success status
        return true;
    }
}
