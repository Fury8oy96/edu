<?php

namespace App\Services;

use App\Exceptions\VideoInUseException;
use App\Exceptions\VideoNotFoundException;
use App\Models\Video;
use App\Models\VideoQuality;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoService
{
    /**
     * Get all videos with optional filtering
     * 
     * @param array $filters ['status' => string, 'search' => string, 'lesson_id' => int]
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function listVideos(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Video::query()->with(['qualities', 'lessons']);

        // Apply status filter
        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search filter on filename
        if (isset($filters['search']) && !empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('original_filename', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('display_name', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        // Apply lesson filter using relationship
        if (isset($filters['lesson_id']) && !empty($filters['lesson_id'])) {
            $query->whereHas('lessons', function ($q) use ($filters) {
                $q->where('lesson_id', $filters['lesson_id']);
            });
        }

        // Add lesson count for each video
        $query->withCount('lessons');

        // Order by most recent first
        $query->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get video details by ID
     * 
     * @param int $videoId Video identifier
     * @return Video
     * @throws VideoNotFoundException
     */
    public function getVideo(int $videoId): Video
    {
        $video = Video::with(['qualities', 'lessons', 'uploader'])->find($videoId);

        if (!$video) {
            throw new VideoNotFoundException($videoId);
        }

        return $video;
    }

    /**
     * Update video metadata
     * 
     * @param int $videoId Video identifier
     * @param array $data ['display_name' => string]
     * @return Video Updated video
     * @throws VideoNotFoundException
     */
    public function updateVideo(int $videoId, array $data): Video
    {
        $video = Video::find($videoId);

        if (!$video) {
            throw new VideoNotFoundException($videoId);
        }

        // Validate display_name is not empty
        if (isset($data['display_name'])) {
            $displayName = trim($data['display_name']);
            if (empty($displayName)) {
                throw new \InvalidArgumentException('Display name cannot be empty');
            }
            $video->display_name = $displayName;
        }

        // Only allow display_name changes - prevent technical metadata changes
        // Technical metadata fields (duration, resolution, codec, format) are not updated
        // even if they are present in $data

        $video->save();

        return $video->fresh(['qualities', 'lessons']);
    }

    /**
     * Delete video and all associated files
     * 
     * @param int $videoId Video identifier
     * @return bool Success status
     * @throws VideoInUseException
     * @throws VideoNotFoundException
     */
    public function deleteVideo(int $videoId): bool
    {
        $video = Video::with(['qualities', 'lessons'])->find($videoId);

        if (!$video) {
            throw new VideoNotFoundException($videoId);
        }

        // Check if video has active lessons
        if ($video->hasActiveLessons()) {
            $lessonIds = $video->lessons()->pluck('lesson_id')->toArray();
            throw new VideoInUseException($lessonIds);
        }

        // Use database transaction to delete video record and all associations
        DB::beginTransaction();

        try {
            // Delete video record (cascade will handle video_qualities and video_lesson pivot)
            $video->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete video from database', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        // Delete files from storage (outside transaction)
        // Log errors for individual file deletions but continue

        // Delete original video file
        if ($video->original_path) {
            try {
                Storage::disk('videos')->delete($video->original_path);
            } catch (\Exception $e) {
                Log::error('Failed to delete original video file', [
                    'video_id' => $videoId,
                    'path' => $video->original_path,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Delete all quality files
        foreach ($video->qualities as $quality) {
            if ($quality->file_path) {
                try {
                    Storage::disk('videos')->delete($quality->file_path);
                } catch (\Exception $e) {
                    Log::error('Failed to delete quality video file', [
                        'video_id' => $videoId,
                        'quality' => $quality->quality,
                        'path' => $quality->file_path,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Delete thumbnail
        if ($video->thumbnail_path) {
            try {
                Storage::disk('videos')->delete($video->thumbnail_path);
            } catch (\Exception $e) {
                Log::error('Failed to delete thumbnail file', [
                    'video_id' => $videoId,
                    'path' => $video->thumbnail_path,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return true;
    }

    /**
     * Bulk delete videos
     * 
     * @param array $videoIds Array of video identifiers
     * @return array ['success' => int[], 'failed' => int[]]
     */
    public function bulkDeleteVideos(array $videoIds): array
    {
        $success = [];
        $failed = [];

        // Validate all video IDs exist
        $videos = Video::with(['lessons'])->whereIn('id', $videoIds)->get();
        $foundIds = $videos->pluck('id')->toArray();
        $notFoundIds = array_diff($videoIds, $foundIds);

        // Add not found IDs to failed list
        foreach ($notFoundIds as $id) {
            $failed[] = [
                'id' => $id,
                'reason' => 'Video not found'
            ];
        }

        // Check each video for active lessons and delete eligible ones
        foreach ($videos as $video) {
            try {
                // Check if video has active lessons
                if ($video->hasActiveLessons()) {
                    $failed[] = [
                        'id' => $video->id,
                        'reason' => 'Video is associated with active lessons'
                    ];
                    continue;
                }

                // Delete the video
                $this->deleteVideo($video->id);
                $success[] = $video->id;
            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $video->id,
                    'reason' => $e->getMessage()
                ];
                Log::error('Failed to delete video in bulk operation', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => $success,
            'failed' => $failed
        ];
    }

    /**
     * Get video processing progress
     * 
     * @param int $videoId Video identifier
     * @return array ['status' => string, 'progress' => float, 'completed_qualities' => array]
     */
    public function getProcessingProgress(int $videoId): array
    {
        $video = Video::with('qualities')->find($videoId);

        if (!$video) {
            throw new VideoNotFoundException($videoId);
        }

        // Get completed qualities
        $completedQualities = $video->qualities()
            ->where('status', 'completed')
            ->pluck('quality')
            ->toArray();

        // Calculate overall progress
        $totalQualities = 4; // 360p, 480p, 720p, 1080p
        $completedCount = count($completedQualities);
        $progress = $totalQualities > 0 ? ($completedCount / $totalQualities) * 100 : 0;

        return [
            'status' => $video->status,
            'progress' => round($progress, 2),
            'completed_qualities' => $completedQualities
        ];
    }

    /**
     * Get video URLs for all quality levels
     * 
     * @param int $videoId Video identifier
     * @return array ['qualities' => array, 'thumbnail' => string|null]
     */
    public function getVideoUrls(int $videoId): array
    {
        $video = Video::with('qualities')->find($videoId);

        if (!$video) {
            throw new VideoNotFoundException($videoId);
        }

        $qualities = [];

        // Get URLs for completed quality levels only
        $completedQualities = $video->qualities()
            ->where('status', 'completed')
            ->get();

        foreach ($completedQualities as $quality) {
            $url = $this->generateVideoUrl($quality->file_path);
            if ($url) {
                $qualities[$quality->quality] = $url;
            }
        }

        // Get thumbnail URL
        $thumbnailUrl = null;
        if ($video->thumbnail_path) {
            $thumbnailUrl = $this->generateVideoUrl($video->thumbnail_path);
        }

        return [
            'qualities' => $qualities,
            'thumbnail' => $thumbnailUrl
        ];
    }

    /**
     * Generate signed URL for video file
     * 
     * @param string $path File path
     * @return string|null
     */
    protected function generateVideoUrl(string $path): ?string
    {
        try {
            $disk = Storage::disk('videos');
            
            // Try to generate temporary URL (works for S3)
            // If it fails, fall back to regular URL (for local storage)
            try {
                return $disk->temporaryUrl($path, now()->addHour());
            } catch (\RuntimeException $e) {
                // Fall back to regular URL for local storage
                return $disk->url($path);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate video URL', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
