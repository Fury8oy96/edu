<?php

namespace App\Policies;

use App\Models\BlogComment;
use App\Models\Students;

class BlogCommentPolicy
{
    /**
     * Determine if the student can delete the comment
     * 
     * Allow only if user is the comment author
     * 
     * @param Students $student
     * @param BlogComment $comment
     * @return bool
     */
    public function delete(Students $student, BlogComment $comment): bool
    {
        return $comment->student_id === $student->id;
    }
}
