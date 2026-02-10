<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Video extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'original_filename',
        'display_name',
        'file_size',
        'duration',
        'resolution',
        'codec',
        'format',
        'original_path',
        'thumbnail_path',
        'status',
        'processing_progress',
        'error_message',
        'uploaded_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'duration' => 'decimal:2',
            'processing_progress' => 'integer',
        ];
    }

    /**
     * Get the video quality levels associated with this video.
     *
     * @return HasMany
     */
    public function qualities(): HasMany
    {
        return $this->hasMany(VideoQuality::class);
    }

    /**
     * Get the lessons associated with this video.
     *
     * @return BelongsToMany
     */
    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lessons::class, 'video_lesson', 'video_id', 'lesson_id')
            ->withPivot('attached_at');
    }

    /**
     * Get the user who uploaded this video.
     *
     * @return BelongsTo
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Check if the video is currently being processed.
     * Returns true if status is 'pending' or 'processing'.
     *
     * @return bool
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Check if the video processing is completed.
     * Returns true if status is 'completed'.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the video is associated with any active (published) lessons.
     * Currently checks if the video has any associated lessons.
     * 
     * Note: The lessons table doesn't currently have an is_published field.
     * This implementation prevents deletion of videos that are in use by any lesson.
     * If lesson publishing status is added in the future, this method should be updated
     * to filter by published status.
     *
     * @return bool
     */
    public function hasActiveLessons(): bool
    {
        return $this->lessons()->exists();
    }
}
