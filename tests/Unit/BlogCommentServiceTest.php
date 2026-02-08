<?php

use App\Models\BlogPost;
use App\Models\BlogComment;
use App\Models\Students;
use App\Services\BlogCommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);

beforeEach(function () {
    // Create service
    $this->commentService = new BlogCommentService();
    
    // Create a test student
    $this->student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create another student for testing
    $this->anotherStudent = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
});

describe('createComment', function () {
    test('creates comment on published blog post', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $content = 'This is a test comment.';
        $comment = $this->commentService->createComment($blogPost, $this->student, $content);
        
        expect($comment)->toBeInstanceOf(BlogComment::class);
        expect($comment->content)->toBe($content);
        expect($comment->blog_post_id)->toBe($blogPost->id);
        expect($comment->student_id)->toBe($this->student->id);
        expect($comment->relationLoaded('student'))->toBeTrue();
    });
    
    test('throws authorization exception when commenting on draft post', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        $content = 'This should fail.';
        
        expect(fn() => $this->commentService->createComment($blogPost, $this->student, $content))
            ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    });
    
    test('creates comment with correct associations', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $content = 'Great post!';
        $comment = $this->commentService->createComment($blogPost, $this->student, $content);
        
        // Verify database record
        $this->assertDatabaseHas('blog_comments', [
            'id' => $comment->id,
            'blog_post_id' => $blogPost->id,
            'student_id' => $this->student->id,
            'content' => $content,
        ]);
    });
    
    test('eager loads student relationship', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $comment = $this->commentService->createComment($blogPost, $this->student, 'Test comment');
        
        expect($comment->relationLoaded('student'))->toBeTrue();
        expect($comment->student->id)->toBe($this->student->id);
    });
    
    test('multiple students can comment on same post', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $comment1 = $this->commentService->createComment($blogPost, $this->student, 'First comment');
        $comment2 = $this->commentService->createComment($blogPost, $this->anotherStudent, 'Second comment');
        
        expect($comment1->student_id)->toBe($this->student->id);
        expect($comment2->student_id)->toBe($this->anotherStudent->id);
        expect($blogPost->comments()->count())->toBe(2);
    });
    
    test('same student can comment multiple times on same post', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $comment1 = $this->commentService->createComment($blogPost, $this->student, 'First comment');
        $comment2 = $this->commentService->createComment($blogPost, $this->student, 'Second comment');
        
        expect($comment1->id)->not->toBe($comment2->id);
        expect($blogPost->comments()->count())->toBe(2);
    });
});

describe('deleteComment', function () {
    test('deletes comment successfully', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $comment = BlogComment::factory()->create([
            'blog_post_id' => $blogPost->id,
            'student_id' => $this->student->id,
            'content' => 'Test comment',
        ]);
        
        $result = $this->commentService->deleteComment($comment);
        
        expect($result)->toBeTrue();
        expect(BlogComment::find($comment->id))->toBeNull();
    });
    
    test('returns true when comment is deleted', function () {
        $comment = BlogComment::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        $result = $this->commentService->deleteComment($comment);
        
        expect($result)->toBeTrue();
    });
    
    test('removes comment from database', function () {
        $comment = BlogComment::factory()->create([
            'student_id' => $this->student->id,
            'content' => 'To be deleted',
        ]);
        
        $commentId = $comment->id;
        $this->commentService->deleteComment($comment);
        
        $this->assertDatabaseMissing('blog_comments', [
            'id' => $commentId,
        ]);
    });
});

describe('getComments', function () {
    test('returns comments for a blog post', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        BlogComment::factory()->count(3)->create([
            'blog_post_id' => $blogPost->id,
        ]);
        
        $result = $this->commentService->getComments($blogPost->id);
        
        expect($result->total())->toBe(3);
    });
    
    test('orders comments by created_at ascending', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        // Create comments with different timestamps
        $comment1 = BlogComment::factory()->create([
            'blog_post_id' => $blogPost->id,
            'created_at' => now()->subHours(3),
        ]);
        $comment2 = BlogComment::factory()->create([
            'blog_post_id' => $blogPost->id,
            'created_at' => now()->subHours(1),
        ]);
        $comment3 = BlogComment::factory()->create([
            'blog_post_id' => $blogPost->id,
            'created_at' => now()->subHours(2),
        ]);
        
        $result = $this->commentService->getComments($blogPost->id);
        
        expect($result->items()[0]->id)->toBe($comment1->id); // Oldest first
        expect($result->items()[1]->id)->toBe($comment3->id);
        expect($result->items()[2]->id)->toBe($comment2->id); // Newest last
    });
    
    test('eager loads student relationship', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        BlogComment::factory()->create([
            'blog_post_id' => $blogPost->id,
            'student_id' => $this->student->id,
        ]);
        
        $result = $this->commentService->getComments($blogPost->id);
        
        $firstComment = $result->items()[0];
        expect($firstComment->relationLoaded('student'))->toBeTrue();
        expect($firstComment->student->id)->toBe($this->student->id);
    });
    
    test('returns only comments for specified blog post', function () {
        $blogPost1 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        $blogPost2 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        BlogComment::factory()->count(2)->create([
            'blog_post_id' => $blogPost1->id,
        ]);
        BlogComment::factory()->count(3)->create([
            'blog_post_id' => $blogPost2->id,
        ]);
        
        $result = $this->commentService->getComments($blogPost1->id);
        
        expect($result->total())->toBe(2);
        foreach ($result->items() as $comment) {
            expect($comment->blog_post_id)->toBe($blogPost1->id);
        }
    });
    
    test('paginates results with default page size', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        BlogComment::factory()->count(25)->create([
            'blog_post_id' => $blogPost->id,
        ]);
        
        $result = $this->commentService->getComments($blogPost->id);
        
        expect($result->perPage())->toBe(20);
        expect($result->count())->toBe(20);
        expect($result->total())->toBe(25);
    });
    
    test('paginates results with custom page size', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        BlogComment::factory()->count(25)->create([
            'blog_post_id' => $blogPost->id,
        ]);
        
        $result = $this->commentService->getComments($blogPost->id, 10);
        
        expect($result->perPage())->toBe(10);
        expect($result->count())->toBe(10);
    });
    
    test('caps page size at maximum of 100', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        BlogComment::factory()->count(150)->create([
            'blog_post_id' => $blogPost->id,
        ]);
        
        $result = $this->commentService->getComments($blogPost->id, 200);
        
        expect($result->perPage())->toBe(100);
    });
    
    test('returns empty paginator when no comments exist', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->commentService->getComments($blogPost->id);
        
        expect($result->total())->toBe(0);
        expect($result->items())->toBeEmpty();
    });
    
    test('handles pagination correctly across multiple pages', function () {
        $blogPost = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        BlogComment::factory()->count(25)->create([
            'blog_post_id' => $blogPost->id,
        ]);
        
        $page1 = $this->commentService->getComments($blogPost->id, 10);
        
        expect($page1->currentPage())->toBe(1);
        expect($page1->lastPage())->toBe(3);
        expect($page1->count())->toBe(10);
    });
});
