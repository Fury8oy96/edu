<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to assemble uploaded chunks into a complete video file.
 * 
 * This is a placeholder implementation. Full implementation will be done in task 6.1-6.2.
 */
class ChunkAssemblyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // No retries for assembly
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $sessionId
    ) {}

    /**
     * Execute the job.
     * 
     * Assembles uploaded chunks into a complete video file, creates video record,
     * extracts metadata, and dispatches transcoding jobs.
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info('ChunkAssemblyJob started', [
            'session_id' => $this->sessionId,
        ]);

        try {
            // Retrieve session and validate completeness
            $session = \App\Models\UploadSession::where('session_id', $this->sessionId)->first();
            
            if (!$session) {
                throw new \App\Exceptions\InvalidSessionException($this->sessionId);
            }
            
            if (!$session->isComplete()) {
                throw new \App\Exceptions\IncompleteUploadException(
                    array_diff(
                        range(0, $session->total_chunks - 1),
                        $session->received_chunks
                    )
                );
            }

            // Assemble chunks in order into single file
            $tempAssembledPath = $this->assembleChunks($session);

            // Use database transaction for critical operations
            $video = \Illuminate\Support\Facades\DB::transaction(function () use ($session, $tempAssembledPath) {
                // Generate unique path for permanent storage
                $originalPath = $this->generateVideoPath($session);
                
                // Move assembled file to permanent storage
                $assembledContent = file_get_contents($tempAssembledPath);
                \Illuminate\Support\Facades\Storage::disk('local')->put($originalPath, $assembledContent);
                
                // Create Video record with status "pending"
                $video = \App\Models\Video::create([
                    'original_filename' => $session->filename,
                    'display_name' => $session->filename,
                    'file_size' => $session->file_size,
                    'original_path' => $originalPath,
                    'status' => 'pending',
                    'processing_progress' => 0,
                    'uploaded_by' => auth()->id(),
                ]);

                // Delete chunk files
                $chunkDirectory = "temp/uploads/{$this->sessionId}";
                if (\Illuminate\Support\Facades\Storage::exists($chunkDirectory)) {
                    \Illuminate\Support\Facades\Storage::deleteDirectory($chunkDirectory);
                }

                // Update session status
                $session->status = 'completed';
                $session->save();

                return $video;
            });

            // Clean up temporary assembled file
            if (file_exists($tempAssembledPath)) {
                unlink($tempAssembledPath);
            }

            // Extract metadata using FFMPEGService and update Video record
            $this->extractAndUpdateMetadata($video);

            // Create VideoQuality records for each quality level
            $qualityLevels = ['360p', '480p', '720p', '1080p'];
            foreach ($qualityLevels as $quality) {
                \App\Models\VideoQuality::create([
                    'video_id' => $video->id,
                    'quality' => $quality,
                    'file_path' => '', // Will be set by transcoding job
                    'file_size' => 0,
                    'status' => 'pending',
                    'processing_progress' => 0,
                ]);
            }

            // Dispatch VideoTranscodingJob for each quality level
            foreach ($qualityLevels as $quality) {
                VideoTranscodingJob::dispatch($video->id, $quality);
            }

            // Dispatch ThumbnailGenerationJob
            ThumbnailGenerationJob::dispatch($video->id);

            \Illuminate\Support\Facades\Log::info('ChunkAssemblyJob completed successfully', [
                'session_id' => $this->sessionId,
                'video_id' => $video->id,
            ]);

        } catch (\Exception $e) {
            // On failure: mark session as failed, retain chunks, log error
            \Illuminate\Support\Facades\Log::error('ChunkAssemblyJob failed', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark session as failed (if session exists)
            $session = \App\Models\UploadSession::where('session_id', $this->sessionId)->first();
            if ($session) {
                $session->status = 'failed';
                $session->save();
            }

            // Re-throw exception to mark job as failed
            throw $e;
        }
    }

    /**
     * Assemble chunks in order into a single temporary file.
     *
     * @param \App\Models\UploadSession $session
     * @return string Path to temporary assembled file
     */
    private function assembleChunks(\App\Models\UploadSession $session): string
    {
        $tempPath = sys_get_temp_dir() . '/video_assembly_' . $this->sessionId . '.tmp';
        $outputHandle = fopen($tempPath, 'wb');

        if (!$outputHandle) {
            throw new \RuntimeException("Failed to create temporary file for assembly");
        }

        try {
            // Sort chunks to ensure correct order
            $chunks = $session->received_chunks;
            sort($chunks);

            // Append each chunk to the output file
            foreach ($chunks as $chunkNumber) {
                $chunkPath = "temp/uploads/{$this->sessionId}/chunk_{$chunkNumber}";
                
                if (!\Illuminate\Support\Facades\Storage::exists($chunkPath)) {
                    throw new \RuntimeException("Chunk {$chunkNumber} not found");
                }

                $chunkContent = \Illuminate\Support\Facades\Storage::get($chunkPath);
                fwrite($outputHandle, $chunkContent);
            }

            fclose($outputHandle);

            return $tempPath;
        } catch (\Exception $e) {
            if (is_resource($outputHandle)) {
                fclose($outputHandle);
            }
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            throw $e;
        }
    }

    /**
     * Generate unique path for video in permanent storage.
     *
     * @param \App\Models\UploadSession $session
     * @return string Storage path
     */
    private function generateVideoPath(\App\Models\UploadSession $session): string
    {
        // Generate unique identifier for this video
        $uniqueId = \Illuminate\Support\Str::uuid();
        
        // Extract file extension from original filename
        $extension = pathinfo($session->filename, PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'mp4'; // Default to mp4
        }

        // Create path: videos/{uuid}/original.{ext}
        return "videos/{$uniqueId}/original.{$extension}";
    }

    /**
     * Extract metadata from video and update Video record.
     *
     * @param \App\Models\Video $video
     * @return void
     */
    private function extractAndUpdateMetadata(\App\Models\Video $video): void
    {
        try {
            $ffmpegService = app(\App\Services\FFMPEGService::class);
            
            // Get full path to video file
            $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($video->original_path);
            
            // Extract metadata
            $metadata = $ffmpegService->extractMetadata($fullPath);

            // Update video record with metadata
            $video->duration = $metadata['duration'];
            $video->resolution = $metadata['resolution'];
            $video->codec = $metadata['codec'];
            $video->format = $metadata['format'];
            $video->status = 'processing'; // Update status to processing
            $video->save();

            \Illuminate\Support\Facades\Log::info('Video metadata extracted', [
                'video_id' => $video->id,
                'metadata' => $metadata,
            ]);

        } catch (\App\Exceptions\FFMPEGException $e) {
            \Illuminate\Support\Facades\Log::error('Failed to extract video metadata', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            // Mark video as failed
            $video->status = 'failed';
            $video->error_message = 'Failed to extract metadata: ' . $e->getMessage();
            $video->save();

            throw $e;
        }
    }
}
