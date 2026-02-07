<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courses extends Model
{
    /** @use HasFactory<\Database\Factories\CoursesFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'price',
        'is_paid',
        'status',
        'language',
        'level',
        'category',
        'subcategory',
        'tags',
        'keywords',
        'requirements',
        'outcomes',
        'target_audience',
        'instructor_id',
        'published_at',
        'duration_hours',
        'enrollment_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'keywords' => 'array',
        'is_paid' => 'boolean',
        'published_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructors::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Modules::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lessons::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Students::class, 'course_student')
                    ->withPivot('enrolled_at', 'status', 'progress_percentage')
                    ->withTimestamps();
    }
}
