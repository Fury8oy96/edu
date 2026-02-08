<?php

use App\Http\Resources\BlogCommentResource;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('BlogCommentResource transforms comment model correctly', function () {
    // Create a student (author)
    $student = Students::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Post',
        'slug' => 'test-post',
        'content' => 'Test content',
        'excerpt' => 'Test excerpt',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    // Create a comment
    $comment = BlogComment::create([
        'blog_post_id' => $blogPost->id,
        'student_id' => $student->id,
        'content' => 'This is a great post!',
    ]);
    
    // Load relationships
    $comment->load('student');
    
    // Transform using resource
    $resource = new BlogCommentResource($comment);
    $array = $resource->toArray(request());
    
    // Assert all required fields are present
    expect($array)->toHaveKeys(['id', 'content', 'created_at', 'author']);
    
    // Assert values are correct
    expect($array['id'])->toBe($comment->id);
    expect($array['content'])->toBe('This is a great post!');
    expect($array['created_at'])->toBeString();
    
    // Assert author structure
    expect($array['author'])->toBeArray();
    expect($array['author'])->toHaveKeys(['id', 'name', 'avatar']);
    expect($array['author']['id'])->toBe($student->id);
    expect($array['author']['name'])->toBe('John Doe');
});

test('BlogCommentResource includes is_author true for comment author', function () {
    // Create a student (comment author)
    $student = Students::create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Post',
        'slug' => 'test-post',
        'content' => 'Test content',
        'excerpt' => 'Test excerpt',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    // Create a comment
    $comment = BlogComment::create([
        'blog_post_id' => $blogPost->id,
        'student_id' => $student->id,
        'content' => 'My own comment',
    ]);
    
    // Load relationships
    $comment->load('student');
    
    // Create authenticated request with the comment author
    $request = Request::create('/api/comments/' . $comment->id, 'GET');
    $request->setUserResolver(fn() => $student);
    
    // Transform using resource
    $resource = new BlogCommentResource($comment);
    $array = $resource->toArray($request);
    
    // Assert is_author is true
    expect($array)->toHaveKey('is_author');
    expect($array['is_author'])->toBeTrue();
});

test('BlogCommentResource includes is_author false for different user', function () {
    // Create two students
    $author = Students::create([
        'name' => 'Comment Author',
        'email' => 'author@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    $viewer = Students::create([
        'name' => 'Viewer',
        'email' => 'viewer@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post
    $blogPost = BlogPost::create([
        'student_id' => $author->id,
        'title' => 'Test Post',
        'slug' => 'test-post',
        'content' => 'Test content',
        'excerpt' => 'Test excerpt',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    // Create a comment by author
    $comment = BlogComment::create([
        'blog_post_id' => $blogPost->id,
        'student_id' => $author->id,
        'content' => 'Comment by author',
    ]);
    
    // Load relationships
    $comment->load('student');
    
    // Create authenticated request with different user (viewer)
    $request = Request::create('/api/comments/' . $comment->id, 'GET');
    $request->setUserResolver(fn() => $viewer);
    
    // Transform using resource
    $resource = new BlogCommentResource($comment);
    $array = $resource->toArray($request);
    
    // Assert is_author is false
    expect($array)->toHaveKey('is_author');
    expect($array['is_author'])->toBeFalse();
});

test('BlogCommentResource excludes is_author for unauthenticated request', function () {
    // Create a student
    $student = Students::create([
        'name' => 'Author',
        'email' => 'author@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Post',
        'slug' => 'test-post',
        'content' => 'Test content',
        'excerpt' => 'Test excerpt',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    // Create a comment
    $comment = BlogComment::create([
        'blog_post_id' => $blogPost->id,
        'student_id' => $student->id,
        'content' => 'Public comment',
    ]);
    
    // Load relationships
    $comment->load('student');
    
    // Create unauthenticated request
    $request = Request::create('/api/comments/' . $comment->id, 'GET');
    
    // Transform using resource
    $resource = new BlogCommentResource($comment);
    $array = $resource->toArray($request);
    
    // Assert is_author is not included
    expect($array)->not->toHaveKey('is_author');
});

test('BlogCommentResource handles null avatar gracefully', function () {
    // Create a student without avatar
    $student = Students::create([
        'name' => 'No Avatar User',
        'email' => 'noavatar@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
        'avatar' => null,
    ]);
    
    // Create a blog post
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Post',
        'slug' => 'test-post',
        'content' => 'Test content',
        'excerpt' => 'Test excerpt',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    // Create a comment
    $comment = BlogComment::create([
        'blog_post_id' => $blogPost->id,
        'student_id' => $student->id,
        'content' => 'Comment without avatar',
    ]);
    
    // Load relationships
    $comment->load('student');
    
    // Transform using resource
    $resource = new BlogCommentResource($comment);
    $array = $resource->toArray(request());
    
    // Assert avatar is null
    expect($array['author']['avatar'])->toBeNull();
});
