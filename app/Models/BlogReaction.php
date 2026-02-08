<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_post_id',
        'student_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Only use created_at timestamp, no updated_at
    const UPDATED_AT = null;

    /**
     * Get the blog post this reaction belongs to
     */
    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }

    /**
     * Get the student who reacted
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class, 'student_id');
    }
}
