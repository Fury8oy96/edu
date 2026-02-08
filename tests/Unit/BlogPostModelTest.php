<?php

use App\Models\BlogPost;
use App\Models\Students;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('blog post can be created with required fields', function () {
    $student = Students::factory()->create();
    $category = Category::factory()->create();
    
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Blog Post',
        'slug' => 'test-blog-post',
        'content' => 'This is test content for the blog post.',
        'status' => 'draft',
        'category_id' => $category->id,
    ]);
    
    expect($blogPost)->toBeInstanceOf(BlogPost::class)
        ->and($blogPost->title)->toBe('Test Blog Post')
        ->and($blogPost->status)->toBe('draft')
        ->and($blogPost->student_id)->toBe($student->id);
});

test('blog post defaults to draft status', function () {
    $student = Students::factory()->create();
    
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Blog Post',
        'slug' => 'test-blog-post',
        'content' => 'This is test content.',
    ]);
    
    expect($blogPost->status)->toBe('draft')
        ->and($blogPost->isDraft())->toBeTrue()
        ->and($blogPost->isPublished())->toBeFalse();
});

test('blog post can be published', function () {
    $student = Students::factory()->create();
    
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Blog Post',
        'slug' => 'test-blog-post',
        'content' => 'This is test content.',
        'status' => 'draft',
    ]);
    
    expect($blogPost->published_at)->toBeNull();
    
    $blogPost->publish();
    
    expect($blogPost->status)->toBe('published')
        ->and($blogPost->isPublished())->toBeTrue()
        ->and($blogPost->published_at)->not->toBeNull();
});

test('blog post can be unpublished', function () {
    $student = Students::factory()->create();
    
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Blog Post',
        'slug' => 'test-blog-post',
        'content' => 'This is test content.',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    $blogPost->unpublish();
    
    expect($blogPost->status)->toBe('draft')
        ->and($blogPost->isDraft())->toBeTrue()
        ->and($blogPost->published_at)->toBeNull();
});

test('blog post can generate excerpt from content', function () {
    $student = Students::factory()->create();
    
    $longContent = str_repeat('This is a long content. ', 50);
    
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Blog Post',
        'slug' => 'test-blog-post',
        'content' => $longContent,
    ]);
    
    $excerpt = $blogPost->generateExcerpt(100);
    
    expect(mb_strlen($excerpt))->toBeLessThanOrEqual(103) // 100 + '...'
        ->and($excerpt)->toEndWith('...');
});

test('blog post excerpt does not add ellipsis for short content', function () {
    $student = Students::factory()->create();
    
    $shortContent = 'Short content';
    
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Blog Post',
        'slug' => 'test-blog-post',
        'content' => $shortContent,
    ]);
    
    $excerpt = $blogPost->generateExcerpt(100);
    
    expect($excerpt)->toBe($shortContent)
        ->and($excerpt)->not->toEndWith('...');
});

test('blog post has student relationship', function () {
    $student = Students::factory()->create();
    
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Test Blog Post',
        'slug' => 'test-blog-post',
        'content' => 'This is test content.',
    ]);
    
    expect($blogPost->student)->toBeInstanceOf(Students::class)
        ->and($blogPost->student->id)->toBe($student->id);
});

test('published scope filters published posts', function () {
    $student = Students::factory()->create();
    
    BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Draft Post',
        'slug' => 'draft-post',
        'content' => 'Draft content',
        'status' => 'draft',
    ]);
    
    BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Published Post',
        'slug' => 'published-post',
        'content' => 'Published content',
        'status' => 'published',
    ]);
    
    $publishedPosts = BlogPost::published()->get();
    
    expect($publishedPosts)->toHaveCount(1)
        ->and($publishedPosts->first()->status)->toBe('published');
});

test('draft scope filters draft posts', function () {
    $student = Students::factory()->create();
    
    BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Draft Post',
        'slug' => 'draft-post',
        'content' => 'Draft content',
        'status' => 'draft',
    ]);
    
    BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Published Post',
        'slug' => 'published-post',
        'content' => 'Published content',
        'status' => 'published',
    ]);
    
    $draftPosts = BlogPost::draft()->get();
    
    expect($draftPosts)->toHaveCount(1)
        ->and($draftPosts->first()->status)->toBe('draft');
});

test('byStudent scope filters posts by student', function () {
    $student1 = Students::factory()->create();
    $student2 = Students::factory()->create();
    
    BlogPost::create([
        'student_id' => $student1->id,
        'title' => 'Student 1 Post',
        'slug' => 'student-1-post',
        'content' => 'Content',
    ]);
    
    BlogPost::create([
        'student_id' => $student2->id,
        'title' => 'Student 2 Post',
        'slug' => 'student-2-post',
        'content' => 'Content',
    ]);
    
    $student1Posts = BlogPost::byStudent($student1->id)->get();
    
    expect($student1Posts)->toHaveCount(1)
        ->and($student1Posts->first()->student_id)->toBe($student1->id);
});
