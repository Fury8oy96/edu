<?php

use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use App\Models\BlogReaction;
use App\Models\Category;
use App\Models\Students;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('BlogPostResource transforms blog post model correctly', function () {
    // Create a student (author)
    $student = Students::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Create a category
    $category = Category::create([
        'name' => 'Web Development',
        'slug' => 'web-development',
        'description' => 'Web development topics',
    ]);
    
    // Create a blog post
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'My First Blog Post',
        'slug' => 'my-first-blog-post',
        'content' => 'This is the content of my first blog post.',
        'excerpt' => 'This is the excerpt',
        'featured_image' => 'image.jpg',
        'status' => 'published',
        'category_id' => $category->id,
        'published_at' => now(),
    ]);
    
    // Load relationships
    $blogPost->load(['student', 'category', 'tags']);
    $blogPost->loadCount(['comments', 'reactions']);
    
    // Transform using resource
    $resource = new BlogPostResource($blogPost);
    $array = $resource->toArray(request());
    
    // Assert all required fields are present
    expect($array)->toHaveKeys([
        'id', 'title', 'slug', 'content', 'excerpt', 'featured_image',
        'status', 'published_at', 'created_at', 'updated_at',
        'author', 'category', 'tags', 'comments_count', 'reactions_count'
    ]);
    
    // Assert values are correct
    expect($array['id'])->toBe($blogPost->id);
    expect($array['title'])->toBe('My First Blog Post');
    expect($array['slug'])->toBe('my-first-blog-post');
    expect($array['content'])->toBe('This is the content of my first blog post.');
    expect($array['excerpt'])->toBe('This is the excerpt');
    expect($array['featured_image'])->toBe('image.jpg');
    expect($array['status'])->toBe('published');
    
    // Assert author structure
    expect($array['author'])->toBeArray();
    expect($array['author'])->toHaveKeys(['id', 'name', 'avatar']);
    expect($array['author']['id'])->toBe($student->id);
    expect($array['author']['name'])->toBe('John Doe');
    
    // Assert category structure
    expect($array['category'])->toBeArray();
    expect($array['category'])->toHaveKeys(['id', 'name', 'slug']);
    expect($array['category']['id'])->toBe($category->id);
    expect($array['category']['name'])->toBe('Web Development');
    expect($array['category']['slug'])->toBe('web-development');
    
    // Assert counts
    expect($array['comments_count'])->toBe(0);
    expect($array['reactions_count'])->toBe(0);
});

test('BlogPostResource includes tags correctly', function () {
    // Create a student
    $student = Students::create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Tagged Post',
        'slug' => 'tagged-post',
        'content' => 'This post has tags.',
        'excerpt' => 'Tagged post excerpt',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    // Create and attach tags
    $tag1 = Tag::create(['name' => 'Laravel', 'slug' => 'laravel']);
    $tag2 = Tag::create(['name' => 'PHP', 'slug' => 'php']);
    $blogPost->tags()->attach([$tag1->id, $tag2->id]);
    
    // Load relationships
    $blogPost->load(['student', 'tags']);
    $blogPost->loadCount(['comments', 'reactions']);
    
    // Transform using resource
    $resource = new BlogPostResource($blogPost);
    $array = $resource->toArray(request());
    
    // Assert tags structure
    expect($array['tags'])->toBeArray();
    expect($array['tags'])->toHaveCount(2);
    expect($array['tags'][0])->toHaveKeys(['id', 'name', 'slug']);
    expect($array['tags'][0]['name'])->toBe('Laravel');
    expect($array['tags'][1]['name'])->toBe('PHP');
});

test('BlogPostResource handles null category', function () {
    // Create a student
    $student = Students::create([
        'name' => 'Bob Wilson',
        'email' => 'bob@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post without category
    $blogPost = BlogPost::create([
        'student_id' => $student->id,
        'title' => 'Uncategorized Post',
        'slug' => 'uncategorized-post',
        'content' => 'This post has no category.',
        'excerpt' => 'No category',
        'status' => 'draft',
    ]);
    
    // Load relationships
    $blogPost->load(['student', 'category', 'tags']);
    $blogPost->loadCount(['comments', 'reactions']);
    
    // Transform using resource
    $resource = new BlogPostResource($blogPost);
    $array = $resource->toArray(request());
    
    // Assert category is null
    expect($array['category'])->toBeNull();
});

test('BlogPostResource includes has_reacted for authenticated user', function () {
    // Create two students
    $author = Students::create([
        'name' => 'Author',
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
        'title' => 'Post with Reactions',
        'slug' => 'post-with-reactions',
        'content' => 'This post has reactions.',
        'excerpt' => 'Reactions post',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    // Add reaction from viewer
    BlogReaction::create([
        'blog_post_id' => $blogPost->id,
        'student_id' => $viewer->id,
    ]);
    
    // Load relationships
    $blogPost->load(['student', 'category', 'tags']);
    $blogPost->loadCount(['comments', 'reactions']);
    
    // Create authenticated request
    $request = Request::create('/api/blog-posts/' . $blogPost->id, 'GET');
    $request->setUserResolver(fn() => $viewer);
    
    // Transform using resource
    $resource = new BlogPostResource($blogPost);
    $array = $resource->toArray($request);
    
    // Assert has_reacted is true
    expect($array)->toHaveKey('has_reacted');
    expect($array['has_reacted'])->toBeTrue();
    expect($array['reactions_count'])->toBe(1);
});

test('BlogPostResource has_reacted is false when user has not reacted', function () {
    // Create two students
    $author = Students::create([
        'name' => 'Author',
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
        'title' => 'Post without User Reaction',
        'slug' => 'post-without-user-reaction',
        'content' => 'This post has no reaction from viewer.',
        'excerpt' => 'No reaction',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    // Load relationships
    $blogPost->load(['student', 'category', 'tags']);
    $blogPost->loadCount(['comments', 'reactions']);
    
    // Create authenticated request
    $request = Request::create('/api/blog-posts/' . $blogPost->id, 'GET');
    $request->setUserResolver(fn() => $viewer);
    
    // Transform using resource
    $resource = new BlogPostResource($blogPost);
    $array = $resource->toArray($request);
    
    // Assert has_reacted is false
    expect($array)->toHaveKey('has_reacted');
    expect($array['has_reacted'])->toBeFalse();
});

test('BlogPostResource excludes has_reacted for unauthenticated request', function () {
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
        'title' => 'Public Post',
        'slug' => 'public-post',
        'content' => 'This is a public post.',
        'excerpt' => 'Public',
        'status' => 'published',
        'published_at' => now(),
    ]);
    
    // Load relationships
    $blogPost->load(['student', 'category', 'tags']);
    $blogPost->loadCount(['comments', 'reactions']);
    
    // Create unauthenticated request
    $request = Request::create('/api/blog-posts/' . $blogPost->id, 'GET');
    
    // Transform using resource
    $resource = new BlogPostResource($blogPost);
    $array = $resource->toArray($request);
    
    // Assert has_reacted is not included
    expect($array)->not->toHaveKey('has_reacted');
});
