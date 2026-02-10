<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to generate thumbnail from video.
 * 
 * This is a placeholder implementation. Full implementation will be done in task 9.1-9.2.
 */
class ThumbnailGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $videoId
    ) {}

    /**
     * Execute the job.
     * 
     * Generates a thumbnail from the video at an appropriate time position.
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info('ThumbnailGenerationJob started', [
            'video_id' => $this->videoId,
        ]);

        try {
            // Retrieve Video record
            $video = \App\Models\Video::find($this->videoId);
            
            if (!$video) {
                throw new \App\Exceptions\VideoNotFoundException($this->videoId);
            }

            // Get FFMPEGService
            $ffmpegService = app(\App\Services\FFMPEGService::class);

            // Get full path to original video
            $originalFullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($video->original_path);

            // Determine thumbnail time based on video duration
            $thumbnailTime = 5; // Default to 5 seconds
            if ($video->duration && $video->duration < 5) {
                // For short videos, use 1 second
                $thumbnailTime = 1;
            }

            // Generate output path for thumbnail
            $thumbnailPath = $this->generateThumbnailPath($video);
            $thumbnailFullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($thumbnailPath);

            // Ensure output directory exists
            $thumbnailDir = dirname($thumbnailFullPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            // Generate thumbnail
            $ffmpegService->generateThumbnail(
                $originalFullPath,
                $thumbnailFullPath,
                $thumbnailTime
            );

            // Update Video record with thumbnail_path
            $video->thumbnail_path = $thumbnailPath;
            $video->save();

            \Illuminate\Support\Facades\Log::info('ThumbnailGenerationJob completed successfully', [
                'video_id' => $this->videoId,
                'thumbnail_path' => $thumbnailPath,
                'thumbnail_time' => $thumbnailTime,
            ]);

        } catch (\App\Exceptions\FFMPEGException $e) {
            // Log error but don't fail the job (thumbnail is optional)
            \Illuminate\Support\Facades\Log::warning('ThumbnailGenerationJob FFMPEG error (thumbnail is optional)', [
                'video_id' => $this->videoId,
                'error' => $e->getMessage(),
                'ffmpeg_output' => $e->getFfmpegOutput(),
            ]);

            // Don't re-throw - thumbnail generation failure should not fail the job

        } catch (\Exception $e) {
            // Log error but don't fail the job (thumbnail is optional)
            \Illuminate\Support\Facades\Log::warning('ThumbnailGenerationJob failed (thumbnail is optional)', [
                'video_id' => $this->videoId,
                'error' => $e->getMessage(),
            ]);

            // Don't re-throw - thumbnail generation failure should not fail the job
        }
    }

    /**
     * Generate path for thumbnail file.
     *
     * @param \App\Models\Video $video
     * @return string Storage path
     */
    private function generateThumbnailPath(\App\Models\Video $video): string
    {
        // Extract directory from original path (e.g., "videos/{uuid}")
        $originalDir = dirname($video->original_path);
        
        // Create path: videos/{uuid}/thumbnail.jpg
        return "{$originalDir}/thumbnail.jpg";
    }
}
