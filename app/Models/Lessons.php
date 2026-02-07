<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Lessons extends Model
{
    /** @use HasFactory<\Database\Factories\LessonsFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'duration',
        'module_id',
        'instructor_id',
        'outcomes',
        'keywords',
        'requirements',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'keywords' => 'array',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Modules::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructors::class);
    }

    /**
     * Access course through module relationship
     */
    public function course(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->module?->course,
        );
    }
}
