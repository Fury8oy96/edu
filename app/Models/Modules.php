<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modules extends Model
{
    /** @use HasFactory<\Database\Factories\ModulesFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'course_id',
        'duration',
        'status',
        'keywords',
        'requirements',
        'outcomes',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'keywords' => 'array',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Courses::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lessons::class, 'module_id');
    }
}
