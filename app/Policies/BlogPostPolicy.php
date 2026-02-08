<?php

namespace App\Policies;

use App\Models\BlogPost;
use App\Models\Students;

class BlogPostPolicy
{
    /**
     * Determine if the student can view the blog post
     * 
     * Allow if:
     * - Post is published (anyone can view), OR
     * - User is the author (can view their own drafts)
     * 
     * @param Students|null $student
     * @param BlogPost $blogPost
     * @return bool
     */
    public function view(?Students $student, BlogPost $blogPost): bool
    {
        // If post is published, anyone can view it (including guests)
        if ($blogPost->isPublished()) {
            return true;
        }
        
        // If post is draft, only the author can view it
        // If no student is authenticated, deny access
        if (!$student) {
            return false;
        }
        
        return $blogPost->student_id === $student->id;
    }

    /**
     * Determine if the student can update the blog post
     * 
     * Allow only if user is the author
     * 
     * @param Students $student
     * @param BlogPost $blogPost
     * @return bool
     */
    public function update(Students $student, BlogPost $blogPost): bool
    {
        return $blogPost->student_id === $student->id;
    }

    /**
     * Determine if the student can delete the blog post
     * 
     * Allow only if user is the author
     * 
     * @param Students $student
     * @param BlogPost $blogPost
     * @return bool
     */
    public function delete(Students $student, BlogPost $blogPost): bool
    {
        return $blogPost->student_id === $student->id;
    }
}
