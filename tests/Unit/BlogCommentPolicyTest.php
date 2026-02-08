<?php

use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\Students;
use App\Policies\BlogCommentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('BlogCommentPolicy', function () {
    
    describe('delete method', function () {
        
        it('allows comment author to delete their own comment', function () {
            // Create a student (comment author)
            $author = Students::factory()->create();
            
            // Create another student (blog post author)
            $postAuthor = Students::factory()->create();
            
            // Create a blog post
            $blogPost = BlogPost::factory()->create([
                'student_id' => $postAuthor->id,
                'status' => 'published',
            ]);
            
            // Create a comment by the author
            $comment = BlogComment::factory()->create([
                'blog_post_id' => $blogPost->id,
                'student_id' => $author->id,
                'content' => 'This is my comment',
            ]);
            
            $policy = new BlogCommentPolicy();
            
            // Author can delete their own comment
            expect($policy->delete($author, $comment))->toBeTrue();
        });
        
        it('denies non-author from deleting someone else\'s comment', function () {
            // Create two students
            $author = Students::factory()->create();
            $otherStudent = Students::factory()->create();
            
            // Create a blog post
            $blogPost = BlogPost::factory()->create([
                'student_id' => $author->id,
                'status' => 'published',
            ]);
            
            // Create a comment by the author
            $comment = BlogComment::factory()->create([
                'blog_post_id' => $blogPost->id,
                'student_id' => $author->id,
                'content' => 'This is my comment',
            ]);
            
            $policy = new BlogCommentPolicy();
            
            // Other student cannot delete author's comment
            expect($policy->delete($otherStudent, $comment))->toBeFalse();
        });
        
        it('denies blog post author from deleting comments on their post if they are not the comment author', function () {
            // Create two students
            $postAuthor = Students::factory()->create();
            $commenter = Students::factory()->create();
            
            // Create a blog post by post author
            $blogPost = BlogPost::factory()->create([
                'student_id' => $postAuthor->id,
                'status' => 'published',
            ]);
            
            // Create a comment by the commenter (not the post author)
            $comment = BlogComment::factory()->create([
                'blog_post_id' => $blogPost->id,
                'student_id' => $commenter->id,
                'content' => 'Comment on your post',
            ]);
            
            $policy = new BlogCommentPolicy();
            
            // Post author cannot delete someone else's comment on their post
            expect($policy->delete($postAuthor, $comment))->toBeFalse();
        });
        
    });
    
});
