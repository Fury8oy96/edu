<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'status',
        'category_id',
        'published_at',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    public function student(): BelongsTo
    {
        return $this->belongsTo(Students::class, 'student_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'blog_post_tag');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(BlogReaction::class);
    }

    /**
     * Query Scopes
     */

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeByStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeSearch(Builder $query, string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->whereFullText(['title', 'content'], $searchTerm);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByTag(Builder $query, int $tagId): Builder
    {
        return $query->whereHas('tags', function (Builder $q) use ($tagId) {
            $q->where('tags.id', $tagId);
        });
    }

    public function scopeWithCounts(Builder $query): Builder
    {
        return $query->withCount(['comments', 'reactions']);
    }

    /**
     * Helper Methods
     */

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function publish(): void
    {
        $this->status = 'published';
        $this->published_at = now();
        $this->save();
    }

    public function unpublish(): void
    {
        $this->status = 'draft';
        $this->published_at = null;
        $this->save();
    }

    public function generateExcerpt(int $length = 300): string
    {
        // Strip HTML tags from content
        $plainText = strip_tags($this->content);
        
        // If content is shorter than length, return as is
        if (mb_strlen($plainText) <= $length) {
            return $plainText;
        }
        
        // Truncate to length and add ellipsis
        return mb_substr($plainText, 0, $length) . '...';
    }
}
