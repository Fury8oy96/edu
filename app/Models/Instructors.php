<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instructors extends Model
{
    /** @use HasFactory<\Database\Factories\InstructorsFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'bio',
        'avatar',
        'skills',
        'experience',
        'education',
        'certifications',
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'youtube',
        'website',
        'github',
    ];

    protected $casts = [
        'skills' => 'array',
        'certifications' => 'array',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Courses::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lessons::class);
    }
}
