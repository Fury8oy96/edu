<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Students;
use App\Models\Tag;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BlogPostService
{
    /**
     * Allowed HTML tags for rich text content
     */
    private const ALLOWED_TAGS = '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';

    public function __construct(
        private ImageUploadService $imageUploadService
    ) {}

    /**
     * Create a new blog post
     * 
     * @param Students $student
     * @param array $data ['title', 'content', 'status', 'category_id', 'tags', 'featured_image']
     * @return BlogPost
     */
    public function createBlogPost(Students $student, array $data): BlogPost
    {
        // Generate unique slug from title
        $slug = $this->generateSlug($data['title']);

        // Sanitize HTML content
        $sanitizedContent = $this->sanitizeHtml($data['content']);

        // Prepare blog post data
        $blogPostData = [
            'student_id' => $student->id,
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $sanitizedContent,
            'status' => $data['status'] ?? 'draft',
            'category_id' => $data['category_id'] ?? null,
            'featured_image' => $data['featured_image'] ?? null,
        ];

        // Generate excerpt if not provided
        $blogPostData['excerpt'] = $data['excerpt'] ?? null;

        // Create the blog post
        $blogPost = BlogPost::create($blogPostData);

        // Auto-generate excerpt if not provided
        if (empty($blogPostData['excerpt'])) {
            $blogPost->excerpt = $blogPost->generateExcerpt();
            $blogPost->save();
        }

        // Set published_at if status is published
        if ($blogPost->status === 'published' && empty($blogPost->published_at)) {
            $blogPost->published_at = now();
            $blogPost->save();
        }

        // Sync tags if provided
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->syncTags($blogPost, $data['tags']);
        }

        // Reload relationships
        $blogPost->load(['student', 'category', 'tags']);

        return $blogPost;
    }

    /**
     * Update an existing blog post
     * 
     * @param BlogPost $blogPost
     * @param array $data
     * @return BlogPost
     */
    public function updateBlogPost(BlogPost $blogPost, array $data): BlogPost
    {
        // Prepare update data
        $updateData = [];

        // Update title and regenerate slug if title changed
        if (isset($data['title']) && $data['title'] !== $blogPost->title) {
            $updateData['title'] = $data['title'];
            $updateData['slug'] = $this->generateSlug($data['title'], $blogPost->id);
        }

        // Update content and sanitize if provided
        if (isset($data['content'])) {
            $updateData['content'] = $this->sanitizeHtml($data['content']);
            
            // Regenerate excerpt if content changed
            if (!isset($data['excerpt'])) {
                $blogPost->content = $updateData['content'];
                $updateData['excerpt'] = $blogPost->generateExcerpt();
            }
        }

        // Update excerpt if provided
        if (isset($data['excerpt'])) {
            $updateData['excerpt'] = $data['excerpt'];
        }

        // Update status
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
            
            // Set published_at when transitioning to published
            if ($data['status'] === 'published' && $blogPost->status !== 'published') {
                $updateData['published_at'] = now();
            }
            
            // Clear published_at when transitioning to draft
            if ($data['status'] === 'draft' && $blogPost->status === 'published') {
                $updateData['published_at'] = null;
            }
        }

        // Update category
        if (isset($data['category_id'])) {
            $updateData['category_id'] = $data['category_id'];
        }

        // Update featured image
        if (isset($data['featured_image'])) {
            $updateData['featured_image'] = $data['featured_image'];
        }

        // Update the blog post
        $blogPost->update($updateData);

        // Sync tags if provided
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->syncTags($blogPost, $data['tags']);
        }

        // Reload relationships
        $blogPost->load(['student', 'category', 'tags']);

        return $blogPost->fresh();
    }

    /**
     * Delete a blog post and associated resources
     * 
     * @param BlogPost $blogPost
     * @return bool
     */
    public function deleteBlogPost(BlogPost $blogPost): bool
    {
        // Delete featured image if exists
        if ($blogPost->featured_image) {
            $this->imageUploadService->deleteImage($blogPost->featured_image);
        }

        // Delete the blog post (cascade deletes comments and reactions via database constraints)
        return $blogPost->delete();
    }

    /**
     * Get paginated published blog posts
     * 
     * @param array $filters ['search', 'category_id', 'tag_id']
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPublishedBlogPosts(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Cap page size at maximum of 100
        $perPage = min($perPage, 100);

        // Start with published posts query
        $query = BlogPost::query()
            ->published()
            ->with(['student', 'category', 'tags'])
            ->withCounts();

        // Apply search filter if provided
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply category filter if provided
        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        // Apply tag filter if provided
        if (!empty($filters['tag_id'])) {
            $query->byTag($filters['tag_id']);
        }

        // Order by publication date descending
        $query->orderBy('published_at', 'desc');

        // Return paginated results
        return $query->paginate($perPage);
    }

    /**
     * Publish a draft blog post
     *
     * @param BlogPost $blogPost
     * @return BlogPost
     */
    public function publishBlogPost(BlogPost $blogPost): BlogPost
    {
        $blogPost->status = 'published';
        $blogPost->published_at = now();
        $blogPost->save();

        return $blogPost;
    }

    /**
     * Unpublish a blog post (revert to draft)
     *
     * @param BlogPost $blogPost
     * @return BlogPost
     */
    public function unpublishBlogPost(BlogPost $blogPost): BlogPost
    {
        $blogPost->status = 'draft';
        $blogPost->published_at = null;
        $blogPost->save();

        return $blogPost;
    }


    /**
     * Get a student's blog posts (including drafts)
     * 
     * @param int $studentId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getStudentBlogPosts(int $studentId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Cap page size at maximum of 100
        $perPage = min($perPage, 100);

        // Query all posts by student (both draft and published)
        return BlogPost::query()
            ->byStudent($studentId)
            ->with(['student', 'category', 'tags'])
            ->withCounts()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get a single blog post with authorization check
     * 
     * @param int $blogPostId
     * @param Students|null $student
     * @return BlogPost
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getBlogPost(int $blogPostId, ?Students $student = null): BlogPost
    {
        // Find the blog post
        $blogPost = BlogPost::with(['student', 'category', 'tags'])
            ->withCounts()
            ->findOrFail($blogPostId);

        // Check authorization using policy
        if (!$student || !$student->can('view', $blogPost)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('This action is unauthorized.');
        }

        return $blogPost;
    }

    /**
     * Sync tags for a blog post
     * 
     * @param BlogPost $blogPost
     * @param array $tagNames
     * @return void
     */
    private function syncTags(BlogPost $blogPost, array $tagNames): void
    {
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            // Skip empty tag names
            if (empty(trim($tagName))) {
                continue;
            }

            // Generate slug for the tag
            $slug = Str::slug($tagName);

            // Find or create the tag
            $tag = Tag::firstOrCreate(
                ['slug' => $slug],
                ['name' => $tagName]
            );

            $tagIds[] = $tag->id;
        }

        // Sync the tags to the blog post
        $blogPost->tags()->sync($tagIds);
    }

    /**
     * Generate unique slug from title
     * 
     * @param string $title
     * @param int|null $excludeId
     * @return string
     */
    private function generateSlug(string $title, ?int $excludeId = null): string
    {
        // Generate base slug from title
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        // Check for uniqueness and append number if needed
        while (true) {
            $query = BlogPost::where('slug', $slug);
            
            // Exclude current post if updating
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            
            // If slug is unique, break
            if (!$query->exists()) {
                break;
            }
            
            // Append counter and try again
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Sanitize HTML content to prevent XSS attacks
     * 
     * @param string $content
     * @return string
     */
    private function sanitizeHtml(string $content): string
    {
        // Strip all tags except allowed ones
        $sanitized = strip_tags($content, self::ALLOWED_TAGS);
        
        // Remove event handlers and dangerous attributes
        $sanitized = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $sanitized);
        $sanitized = preg_replace('/\s*on\w+\s*=\s*\S+/i', '', $sanitized);
        
        // Remove javascript: protocol from links
        $sanitized = preg_replace('/href\s*=\s*["\']?\s*javascript:/i', 'href="#"', $sanitized);
        
        return $sanitized;
    }
}
