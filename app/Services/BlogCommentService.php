<?php

namespace App\Services;

use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\Students;
use Illuminate\Auth\Access\AuthorizationException;

class BlogCommentService
{
    /**
     * Create a comment on a blog post
     * 
     * @param BlogPost $blogPost
     * @param Students $student
     * @param string $content
     * @return BlogComment
     * @throws AuthorizationException
     */
    public function createComment(BlogPost $blogPost, Students $student, string $content): BlogComment
    {
        // Validate that the blog post is published
        if (!$blogPost->isPublished()) {
            throw new AuthorizationException('Cannot comment on a draft blog post.');
        }

        // Create the comment
        $comment = BlogComment::create([
            'blog_post_id' => $blogPost->id,
            'student_id' => $student->id,
            'content' => $content,
        ]);

        // Eager load the student relationship
        $comment->load('student');

        return $comment;
    }

    /**
     * Delete a comment
     * 
     * @param BlogComment $comment
     * @return bool
     */
    public function deleteComment(BlogComment $comment): bool
    {
        return $comment->delete();
    }

    /**
     * Get comments for a blog post
     * 
     * @param int $blogPostId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getComments(int $blogPostId, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Cap page size at maximum of 100
        $perPage = min($perPage, 100);

        // Query comments for the blog post
        return BlogComment::query()
            ->where('blog_post_id', $blogPostId)
            ->with('student')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }
}
