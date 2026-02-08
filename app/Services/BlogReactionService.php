<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogReaction;
use App\Models\Students;

class BlogReactionService
{
    /**
     * Toggle reaction on a blog post (add if not exists, remove if exists)
     * 
     * @param BlogPost $blogPost
     * @param Students $student
     * @return array ['action' => 'added'|'removed', 'total_reactions' => int]
     */
    public function toggleReaction(BlogPost $blogPost, Students $student): array
    {
        // Check if reaction already exists
        $existingReaction = BlogReaction::query()
            ->where('blog_post_id', $blogPost->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingReaction) {
            // Reaction exists, remove it
            $existingReaction->delete();
            $action = 'removed';
        } else {
            // Reaction doesn't exist, create it
            BlogReaction::create([
                'blog_post_id' => $blogPost->id,
                'student_id' => $student->id,
            ]);
            $action = 'added';
        }

        // Get the updated total reaction count
        $totalReactions = $this->getReactionCount($blogPost->id);

        return [
            'action' => $action,
            'total_reactions' => $totalReactions,
        ];
    }

    /**
     * Check if student has reacted to a blog post
     * 
     * @param int $blogPostId
     * @param int $studentId
     * @return bool
     */
    public function hasReacted(int $blogPostId, int $studentId): bool
    {
        return BlogReaction::query()
            ->where('blog_post_id', $blogPostId)
            ->where('student_id', $studentId)
            ->exists();
    }

    /**
     * Get reaction count for a blog post
     * 
     * @param int $blogPostId
     * @return int
     */
    public function getReactionCount(int $blogPostId): int
    {
        return BlogReaction::query()
            ->where('blog_post_id', $blogPostId)
            ->count();
    }
}
