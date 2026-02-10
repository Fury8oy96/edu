<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;

/**
 * Exception thrown when attempting to delete a video that is associated with active lessons.
 */
class VideoInUseException extends VideoException
{
    protected array $lessonIds;

    public function __construct(array $lessonIds = [])
    {
        $this->lessonIds = $lessonIds;
        
        $message = empty($lessonIds)
            ? "Video is associated with active lessons and cannot be deleted"
            : "Video is associated with active lessons: " . implode(', ', $lessonIds);
            
        parent::__construct($message, 409);
    }

    public function getLessonIds(): array
    {
        return $this->lessonIds;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Video is associated with active lessons and cannot be deleted',
            'lesson_ids' => $this->lessonIds
        ], 409);
    }
}
