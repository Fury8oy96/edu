<?php

namespace App\Services;

use App\Exceptions\LessonNotFoundException;
use App\Exceptions\VideoNotFoundException;
use App\Exceptions\VideoNotReadyException;
use App\Models\Lessons;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing associations between videos and lessons.
 * 
 * This service handles attaching videos to lessons, detaching them,
 * and retrieving lessons associated with a video. All operations
 * use database transactions to ensure data integrity.
 */
class LessonVideoService
{
    /**
     * Attach a video to a lesson.
     * 
     * Validates that the video exists and has status "completed" before
     * attaching it to the lesson. Updates both the lesson's video_url field
     * and creates a pivot record in the video_lesson table.
     * 
     * @param int $lessonId Lesson identifier
     * @param int $videoId Video identifier
     * @return Lessons Updated lesson
     * @throws VideoNotReadyException If video is not completed
     * @throws LessonNotFoundException If lesson does not exist
     * @throws VideoNotFoundException If video does not exist
     */
    public function attachVideoToLesson(int $lessonId, int $videoId): Lessons
    {
        // Validate video exists
        $video = Video::find($videoId);
        if (!$video) {
            throw new VideoNotFoundException($videoId);
        }

        // Validate video has status "completed"
        if (!$video->isCompleted()) {
            throw new VideoNotReadyException(
                "Video is still processing (status: {$video->status}) and cannot be attached to lesson"
            );
        }

        // Validate lesson exists
        $lesson = Lessons::find($lessonId);
        if (!$lesson) {
            throw new LessonNotFoundException("Lesson with ID {$lessonId} not found");
        }

        // Use database transaction to ensure atomicity
        return DB::transaction(function () use ($lesson, $video, $videoId) {
            // Update lesson video_url field
            $lesson->video_url = (string) $videoId;
            $lesson->save();

            // Create or update pivot record with attached_at timestamp
            // Using sync with false to not detach other videos (allows multiple videos per lesson)
            $lesson->videos()->syncWithoutDetaching([
                $videoId => ['attached_at' => now()]
            ]);

            Log::info("Video attached to lesson", [
                'video_id' => $videoId,
                'lesson_id' => $lesson->id,
                'operation' => 'attach_video_to_lesson'
            ]);

            // Reload the lesson to get fresh data
            return $lesson->fresh();
        });
    }

    /**
     * Detach video from a lesson.
     * 
     * Clears the lesson's video_url field and removes the pivot record
     * from the video_lesson table. Uses a database transaction to ensure
     * both operations succeed or fail together.
     * 
     * @param int $lessonId Lesson identifier
     * @return Lessons Updated lesson
     * @throws LessonNotFoundException If lesson does not exist
     */
    public function detachVideoFromLesson(int $lessonId): Lessons
    {
        // Validate lesson exists
        $lesson = Lessons::find($lessonId);
        if (!$lesson) {
            throw new LessonNotFoundException("Lesson with ID {$lessonId} not found");
        }

        // Use database transaction to ensure atomicity
        return DB::transaction(function () use ($lesson) {
            $videoId = $lesson->video_url;

            // Clear lesson video_url field
            $lesson->video_url = null;
            $lesson->save();

            // Remove all pivot records for this lesson
            $lesson->videos()->detach();

            Log::info("Video detached from lesson", [
                'video_id' => $videoId,
                'lesson_id' => $lesson->id,
                'operation' => 'detach_video_from_lesson'
            ]);

            // Reload the lesson to get fresh data
            return $lesson->fresh();
        });
    }

    /**
     * Get all lessons associated with a video.
     * 
     * Returns a collection of lessons that have the specified video
     * attached through the video_lesson pivot table.
     * 
     * @param int $videoId Video identifier
     * @return Collection<Lessons> Collection of lessons associated with the video
     * @throws VideoNotFoundException If video does not exist
     */
    public function getLessonsForVideo(int $videoId): Collection
    {
        // Validate video exists
        $video = Video::find($videoId);
        if (!$video) {
            throw new VideoNotFoundException($videoId);
        }

        // Return collection of associated lessons
        return $video->lessons;
    }
}
