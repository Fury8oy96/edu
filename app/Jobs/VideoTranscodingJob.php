<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to transcode video to a specific quality level.
 * 
 * This is a placeholder implementation. Full implementation will be done in task 8.1-8.2.
 */
class VideoTranscodingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3; // Retry up to 3 times
    public int $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $videoId,
        public string $quality
    ) {}

    /**
     * Execute the job.
     * 
     * Transcodes video to the specified quality level using FFMPEG.
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info('VideoTranscodingJob started', [
            'video_id' => $this->videoId,
            'quality' => $this->quality,
        ]);

        try {
            // Retrieve Video record
            $video = \App\Models\Video::find($this->videoId);
            
            if (!$video) {
                throw new \App\Exceptions\VideoNotFoundException($this->videoId);
            }

            // Retrieve VideoQuality record
            $videoQuality = \App\Models\VideoQuality::where('video_id', $this->videoId)
                ->where('quality', $this->quality)
                ->first();

            if (!$videoQuality) {
                throw new \RuntimeException("VideoQuality record not found for video {$this->videoId} and quality {$this->quality}");
            }

            // Update quality status to "processing"
            $videoQuality->status = 'processing';
            $videoQuality->processing_progress = 0;
            $videoQuality->save();

            // Get FFMPEGService
            $ffmpegService = app(\App\Services\FFMPEGService::class);

            // Get full path to original video
            $originalFullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($video->original_path);

            // Generate output path for transcoded video
            $outputPath = $this->generateTranscodedPath($video, $this->quality);
            $outputFullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($outputPath);

            // Ensure output directory exists
            $outputDir = dirname($outputFullPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            // Transcode video with progress callback
            $progressCallback = function ($progress) use ($videoQuality) {
                // Update processing progress (0-100)
                $videoQuality->processing_progress = min(100, max(0, (int) $progress));
                $videoQuality->save();
            };

            $ffmpegService->transcodeVideo(
                $originalFullPath,
                $outputFullPath,
                $this->quality,
                $progressCallback
            );

            // Get file size of transcoded video
            $fileSize = filesize($outputFullPath);

            // Update VideoQuality with file_path, file_size, status "completed"
            $videoQuality->file_path = $outputPath;
            $videoQuality->file_size = $fileSize;
            $videoQuality->status = 'completed';
            $videoQuality->processing_progress = 100;
            $videoQuality->error_message = null;
            $videoQuality->save();

            // Check if all qualities completed and update Video status to "completed"
            $this->checkAndUpdateVideoCompletion($video);

            \Illuminate\Support\Facades\Log::info('VideoTranscodingJob completed successfully', [
                'video_id' => $this->videoId,
                'quality' => $this->quality,
                'file_size' => $fileSize,
            ]);

        } catch (\App\Exceptions\FFMPEGException $e) {
            // Log FFMPEG error
            \Illuminate\Support\Facades\Log::error('VideoTranscodingJob FFMPEG error', [
                'video_id' => $this->videoId,
                'quality' => $this->quality,
                'error' => $e->getMessage(),
                'ffmpeg_output' => $e->getFfmpegOutput(),
                'attempt' => $this->attempts(),
            ]);

            // Mark quality as failed after 3 retries
            if ($this->attempts() >= $this->tries) {
                $videoQuality = \App\Models\VideoQuality::where('video_id', $this->videoId)
                    ->where('quality', $this->quality)
                    ->first();

                if ($videoQuality) {
                    $videoQuality->status = 'failed';
                    $videoQuality->error_message = 'FFMPEG error: ' . $e->getMessage();
                    $videoQuality->save();
                }

                // Check if other qualities are completed
                $video = \App\Models\Video::find($this->videoId);
                if ($video) {
                    $this->checkAndUpdateVideoCompletion($video);
                }

                \Illuminate\Support\Facades\Log::warning('VideoTranscodingJob failed after max retries', [
                    'video_id' => $this->videoId,
                    'quality' => $this->quality,
                ]);
            } else {
                // Re-throw to trigger retry
                throw $e;
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('VideoTranscodingJob failed', [
                'video_id' => $this->videoId,
                'quality' => $this->quality,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // Mark quality as failed after max retries
            if ($this->attempts() >= $this->tries) {
                $videoQuality = \App\Models\VideoQuality::where('video_id', $this->videoId)
                    ->where('quality', $this->quality)
                    ->first();

                if ($videoQuality) {
                    $videoQuality->status = 'failed';
                    $videoQuality->error_message = $e->getMessage();
                    $videoQuality->save();
                }

                // Check if other qualities are completed
                $video = \App\Models\Video::find($this->videoId);
                if ($video) {
                    $this->checkAndUpdateVideoCompletion($video);
                }
            } else {
                // Re-throw to trigger retry
                throw $e;
            }
        }
    }

    /**
     * Generate path for transcoded video file.
     *
     * @param \App\Models\Video $video
     * @param string $quality
     * @return string Storage path
     */
    private function generateTranscodedPath(\App\Models\Video $video, string $quality): string
    {
        // Extract directory from original path (e.g., "videos/{uuid}")
        $originalDir = dirname($video->original_path);
        
        // Create path: videos/{uuid}/{quality}.mp4
        return "{$originalDir}/{$quality}.mp4";
    }

    /**
     * Check if all quality levels are completed and update video status.
     *
     * @param \App\Models\Video $video
     * @return void
     */
    private function checkAndUpdateVideoCompletion(\App\Models\Video $video): void
    {
        // Get all quality records for this video
        $qualities = \App\Models\VideoQuality::where('video_id', $video->id)->get();

        // Check if all qualities are either completed or failed (not pending or processing)
        $allFinished = $qualities->every(function ($quality) {
            return in_array($quality->status, ['completed', 'failed']);
        });

        if (!$allFinished) {
            return; // Still processing
        }

        // Check if at least one quality is completed
        $hasCompleted = $qualities->contains(function ($quality) {
            return $quality->status === 'completed';
        });

        if ($hasCompleted) {
            // Update video status to "completed"
            $video->status = 'completed';
            $video->processing_progress = 100;
            $video->error_message = null;
            $video->save();

            \Illuminate\Support\Facades\Log::info('Video transcoding completed', [
                'video_id' => $video->id,
                'completed_qualities' => $qualities->where('status', 'completed')->pluck('quality')->toArray(),
                'failed_qualities' => $qualities->where('status', 'failed')->pluck('quality')->toArray(),
            ]);
        } else {
            // All qualities failed
            $video->status = 'failed';
            $video->error_message = 'All quality levels failed to transcode';
            $video->save();

            \Illuminate\Support\Facades\Log::error('Video transcoding failed - all qualities failed', [
                'video_id' => $video->id,
            ]);
        }
    }
}
