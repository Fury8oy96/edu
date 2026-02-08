<?php

use App\Models\BlogPost;
use App\Models\Students;
use App\Models\Category;
use App\Models\Tag;
use App\Services\BlogPostService;
use App\Services\ImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Fake storage
    Storage::fake('public');
    
    // Create service with mocked ImageUploadService
    $this->imageUploadService = Mockery::mock(ImageUploadService::class);
    $this->blogPostService = new BlogPostService($this->imageUploadService);
    
    // Create a test student
    $this->student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a test category
    $this->category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);
});

afterEach(function () {
    Mockery::close();
});

describe('createBlogPost', function () {
    test('creates blog post with valid data', function () {
        $data = [
            'title' => 'My First Blog Post',
            'content' => '<p>This is the content of my blog post.</p>',
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost)->toBeInstanceOf(BlogPost::class);
        expect($blogPost->title)->toBe('My First Blog Post');
        expect($blogPost->content)->toBe('<p>This is the content of my blog post.</p>');
        expect($blogPost->student_id)->toBe($this->student->id);
        expect($blogPost->slug)->toBe('my-first-blog-post');
        expect($blogPost->status)->toBe('draft'); // Default status
        expect($blogPost->excerpt)->not->toBeNull();
    });
    
    test('creates blog post with published status', function () {
        $data = [
            'title' => 'Published Post',
            'content' => '<p>This post is published.</p>',
            'status' => 'published',
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->status)->toBe('published');
        expect($blogPost->published_at)->not->toBeNull();
    });
    
    test('creates blog post with draft status by default', function () {
        $data = [
            'title' => 'Draft Post',
            'content' => '<p>This is a draft.</p>',
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->status)->toBe('draft');
        expect($blogPost->published_at)->toBeNull();
    });
    
    test('creates blog post with category', function () {
        $data = [
            'title' => 'Post with Category',
            'content' => '<p>Content here.</p>',
            'category_id' => $this->category->id,
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->category_id)->toBe($this->category->id);
        expect($blogPost->category->name)->toBe('Test Category');
    });
    
    test('creates blog post with tags', function () {
        $data = [
            'title' => 'Post with Tags',
            'content' => '<p>Content here.</p>',
            'tags' => ['Laravel', 'PHP', 'Web Development'],
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->tags)->toHaveCount(3);
        expect($blogPost->tags->pluck('name')->toArray())->toContain('Laravel', 'PHP', 'Web Development');
    });
    
    test('creates blog post with featured image', function () {
        $data = [
            'title' => 'Post with Image',
            'content' => '<p>Content here.</p>',
            'featured_image' => '/storage/blog-images/test-image.jpg',
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->featured_image)->toBe('/storage/blog-images/test-image.jpg');
    });
    
    test('generates unique slug from title', function () {
        // Create first post
        $data1 = [
            'title' => 'Same Title',
            'content' => '<p>First post.</p>',
        ];
        $blogPost1 = $this->blogPostService->createBlogPost($this->student, $data1);
        
        // Create second post with same title
        $data2 = [
            'title' => 'Same Title',
            'content' => '<p>Second post.</p>',
        ];
        $blogPost2 = $this->blogPostService->createBlogPost($this->student, $data2);
        
        expect($blogPost1->slug)->toBe('same-title');
        expect($blogPost2->slug)->toBe('same-title-1');
    });
    
    test('sanitizes HTML content', function () {
        $data = [
            'title' => 'Post with Script',
            'content' => '<p>Safe content</p><script>alert("XSS")</script><strong>Bold text</strong>',
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->content)->not->toContain('<script>');
        expect($blogPost->content)->toContain('<p>Safe content</p>');
        expect($blogPost->content)->toContain('<strong>Bold text</strong>');
    });
    
    test('auto-generates excerpt from content', function () {
        $longContent = '<p>' . str_repeat('This is a long content. ', 50) . '</p>';
        $data = [
            'title' => 'Long Post',
            'content' => $longContent,
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->excerpt)->not->toBeNull();
        expect(strlen($blogPost->excerpt))->toBeLessThanOrEqual(303); // 300 + '...'
    });
    
    test('uses provided excerpt if given', function () {
        $data = [
            'title' => 'Post with Excerpt',
            'content' => '<p>Full content here.</p>',
            'excerpt' => 'Custom excerpt',
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->excerpt)->toBe('Custom excerpt');
    });
    
    test('creates or finds existing tags', function () {
        // Create a tag first
        Tag::create(['name' => 'Existing Tag', 'slug' => 'existing-tag']);
        
        $data = [
            'title' => 'Post with Mixed Tags',
            'content' => '<p>Content.</p>',
            'tags' => ['Existing Tag', 'New Tag'],
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->tags)->toHaveCount(2);
        expect(Tag::count())->toBe(2); // Only 2 tags total (one was existing)
    });
    
    test('skips empty tag names', function () {
        $data = [
            'title' => 'Post with Empty Tags',
            'content' => '<p>Content.</p>',
            'tags' => ['Valid Tag', '', '   ', 'Another Tag'],
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->tags)->toHaveCount(2);
        expect($blogPost->tags->pluck('name')->toArray())->toContain('Valid Tag', 'Another Tag');
    });
});

describe('updateBlogPost', function () {
    test('updates blog post title', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'title' => 'Original Title',
            'slug' => 'original-title',
        ]);
        
        $data = ['title' => 'Updated Title'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->title)->toBe('Updated Title');
        expect($updated->slug)->toBe('updated-title');
    });
    
    test('updates blog post content', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        $data = ['content' => '<p>Updated content.</p>'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->content)->toBe('<p>Updated content.</p>');
    });
    
    test('updates blog post status from draft to published', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        $data = ['status' => 'published'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->status)->toBe('published');
        expect($updated->published_at)->not->toBeNull();
    });
    
    test('updates blog post status from published to draft', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $data = ['status' => 'draft'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->status)->toBe('draft');
        expect($updated->published_at)->toBeNull();
    });
    
    test('updates blog post category', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'category_id' => null,
        ]);
        
        $data = ['category_id' => $this->category->id];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->category_id)->toBe($this->category->id);
    });
    
    test('updates blog post tags', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        // Add initial tags
        $blogPost->tags()->attach(Tag::factory()->create(['name' => 'Old Tag']));
        
        $data = ['tags' => ['New Tag 1', 'New Tag 2']];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->tags)->toHaveCount(2);
        expect($updated->tags->pluck('name')->toArray())->toContain('New Tag 1', 'New Tag 2');
        expect($updated->tags->pluck('name')->toArray())->not->toContain('Old Tag');
    });
    
    test('updates featured image', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'featured_image' => null,
        ]);
        
        $data = ['featured_image' => '/storage/blog-images/new-image.jpg'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->featured_image)->toBe('/storage/blog-images/new-image.jpg');
    });
    
    test('regenerates slug when title changes', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'title' => 'Original Title',
            'slug' => 'original-title',
        ]);
        
        $data = ['title' => 'New Title'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->slug)->toBe('new-title');
    });
    
    test('does not regenerate slug when title unchanged', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'title' => 'Same Title',
            'slug' => 'same-title',
        ]);
        
        $data = ['content' => '<p>Updated content only.</p>'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->slug)->toBe('same-title');
    });
    
    test('sanitizes HTML content on update', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        $data = ['content' => '<p>Safe</p><script>alert("XSS")</script>'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->content)->not->toContain('<script>');
        expect($updated->content)->toContain('<p>Safe</p>');
    });
    
    test('regenerates excerpt when content changes without explicit excerpt', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'content' => '<p>Old content.</p>',
            'excerpt' => 'Old excerpt',
        ]);
        
        $longContent = '<p>' . str_repeat('New content. ', 50) . '</p>';
        $data = ['content' => $longContent];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->excerpt)->not->toBe('Old excerpt');
        expect($updated->excerpt)->toContain('New content.');
    });
    
    test('uses provided excerpt when updating', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        $data = [
            'content' => '<p>New content.</p>',
            'excerpt' => 'Custom new excerpt',
        ];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->excerpt)->toBe('Custom new excerpt');
    });
    
    test('preserves original creation timestamp', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'created_at' => now()->subDays(5),
        ]);
        
        $originalCreatedAt = $blogPost->created_at;
        
        $data = ['title' => 'Updated Title'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->created_at->timestamp)->toBe($originalCreatedAt->timestamp);
    });
    
    test('preserves author reference', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        $data = ['title' => 'Updated Title'];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->student_id)->toBe($this->student->id);
    });
});

describe('deleteBlogPost', function () {
    test('deletes blog post successfully', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        $this->imageUploadService->shouldReceive('deleteImage')->never();
        
        $result = $this->blogPostService->deleteBlogPost($blogPost);
        
        expect($result)->toBeTrue();
        expect(BlogPost::find($blogPost->id))->toBeNull();
    });
    
    test('deletes featured image when blog post has one', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'featured_image' => '/storage/blog-images/test-image.jpg',
        ]);
        
        $this->imageUploadService
            ->shouldReceive('deleteImage')
            ->once()
            ->with('/storage/blog-images/test-image.jpg')
            ->andReturn(true);
        
        $result = $this->blogPostService->deleteBlogPost($blogPost);
        
        expect($result)->toBeTrue();
    });
    
    test('does not attempt to delete image when blog post has no featured image', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'featured_image' => null,
        ]);
        
        $this->imageUploadService->shouldReceive('deleteImage')->never();
        
        $result = $this->blogPostService->deleteBlogPost($blogPost);
        
        expect($result)->toBeTrue();
    });
});

describe('generateSlug', function () {
    test('generates slug from title', function () {
        $data = [
            'title' => 'This is a Test Title',
            'content' => '<p>Content.</p>',
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->slug)->toBe('this-is-a-test-title');
    });
    
    test('generates unique slug when duplicate exists', function () {
        // Create first post
        BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'title' => 'Duplicate Title',
            'slug' => 'duplicate-title',
        ]);
        
        // Create second post with same title
        $data = [
            'title' => 'Duplicate Title',
            'content' => '<p>Content.</p>',
        ];
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->slug)->toBe('duplicate-title-1');
    });
    
    test('generates unique slug with multiple duplicates', function () {
        // Create multiple posts with same title
        BlogPost::factory()->create(['slug' => 'same-slug']);
        BlogPost::factory()->create(['slug' => 'same-slug-1']);
        BlogPost::factory()->create(['slug' => 'same-slug-2']);
        
        $data = [
            'title' => 'Same Slug',
            'content' => '<p>Content.</p>',
        ];
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->slug)->toBe('same-slug-3');
    });
    
    test('handles special characters in title', function () {
        $data = [
            'title' => 'Title with @#$% Special Characters!',
            'content' => '<p>Content.</p>',
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->slug)->toMatch('/^[a-z0-9-]+$/');
    });
});

describe('syncTags', function () {
    test('syncs tags to blog post', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        $data = ['tags' => ['Tag One', 'Tag Two', 'Tag Three']];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->tags)->toHaveCount(3);
    });
    
    test('creates new tags if they do not exist', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        expect(Tag::count())->toBe(0);
        
        $data = ['tags' => ['New Tag 1', 'New Tag 2']];
        $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect(Tag::count())->toBe(2);
        expect(Tag::where('name', 'New Tag 1')->exists())->toBeTrue();
        expect(Tag::where('name', 'New Tag 2')->exists())->toBeTrue();
    });
    
    test('reuses existing tags', function () {
        // Create existing tag
        Tag::create(['name' => 'Existing Tag', 'slug' => 'existing-tag']);
        
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        $data = ['tags' => ['Existing Tag', 'New Tag']];
        $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect(Tag::count())->toBe(2); // Only one new tag created
    });
    
    test('generates slug for tags', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        $data = ['tags' => ['Tag With Spaces']];
        $this->blogPostService->updateBlogPost($blogPost, $data);
        
        $tag = Tag::where('name', 'Tag With Spaces')->first();
        expect($tag->slug)->toBe('tag-with-spaces');
    });
    
    test('replaces old tags with new tags', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
        ]);
        
        // Add initial tags
        $oldTag = Tag::factory()->create(['name' => 'Old Tag']);
        $blogPost->tags()->attach($oldTag);
        
        // Update with new tags
        $data = ['tags' => ['New Tag']];
        $updated = $this->blogPostService->updateBlogPost($blogPost, $data);
        
        expect($updated->tags)->toHaveCount(1);
        expect($updated->tags->first()->name)->toBe('New Tag');
    });
});

describe('HTML sanitization', function () {
    test('allows safe HTML tags', function () {
        $safeContent = '<p>Paragraph</p><br><strong>Bold</strong><em>Italic</em>' .
                      '<ul><li>List item</li></ul><ol><li>Ordered</li></ol>' .
                      '<a href="#">Link</a><h1>H1</h1><h2>H2</h2><h3>H3</h3>' .
                      '<h4>H4</h4><h5>H5</h5><h6>H6</h6>';
        
        $data = [
            'title' => 'Safe HTML Post',
            'content' => $safeContent,
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->content)->toContain('<p>Paragraph</p>');
        expect($blogPost->content)->toContain('<strong>Bold</strong>');
        expect($blogPost->content)->toContain('<em>Italic</em>');
        expect($blogPost->content)->toContain('<ul>');
        expect($blogPost->content)->toContain('<li>');
        expect($blogPost->content)->toContain('<a href="#">Link</a>');
        expect($blogPost->content)->toContain('<h1>H1</h1>');
    });
    
    test('removes dangerous script tags', function () {
        $dangerousContent = '<p>Safe content</p><script>alert("XSS")</script>';
        
        $data = [
            'title' => 'Dangerous Post',
            'content' => $dangerousContent,
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        // strip_tags removes the <script> tags but keeps the content inside
        expect($blogPost->content)->not->toContain('<script>');
        expect($blogPost->content)->toContain('<p>Safe content</p>');
        // The alert text will remain but without the script tags it's harmless
    });
    
    test('removes iframe tags', function () {
        $content = '<p>Content</p><iframe src="evil.com"></iframe>';
        
        $data = [
            'title' => 'Iframe Post',
            'content' => $content,
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        expect($blogPost->content)->not->toContain('<iframe>');
    });
    
    test('removes onclick and other event handlers', function () {
        $content = '<p onclick="alert(\'XSS\')">Click me</p>';
        
        $data = [
            'title' => 'Event Handler Post',
            'content' => $content,
        ];
        
        $blogPost = $this->blogPostService->createBlogPost($this->student, $data);
        
        // Verify onclick attribute is removed
        expect($blogPost->content)->not->toContain('onclick');
        expect($blogPost->content)->toContain('<p');
        expect($blogPost->content)->toContain('Click me');
    });
});


describe('getPublishedBlogPosts', function () {
    test('returns only published blog posts', function () {
        // Create published and draft posts
        BlogPost::factory()->count(3)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        BlogPost::factory()->count(2)->create([
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        $result = $this->blogPostService->getPublishedBlogPosts();
        
        expect($result->total())->toBe(3);
        foreach ($result->items() as $post) {
            expect($post->status)->toBe('published');
        }
    });
    
    test('orders published posts by publication date descending', function () {
        // Create posts with different publication dates
        $post1 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDays(3),
        ]);
        $post2 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDays(1),
        ]);
        $post3 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDays(2),
        ]);
        
        $result = $this->blogPostService->getPublishedBlogPosts();
        
        expect($result->items()[0]->id)->toBe($post2->id); // Most recent
        expect($result->items()[1]->id)->toBe($post3->id);
        expect($result->items()[2]->id)->toBe($post1->id); // Oldest
    });
    
    test('filters by search term', function () {
        // Skip this test in SQLite as it doesn't support fulltext search
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not support fulltext search');
        }
        
        BlogPost::factory()->create([
            'title' => 'Laravel Tutorial',
            'content' => '<p>Learn Laravel framework</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);
        BlogPost::factory()->create([
            'title' => 'PHP Basics',
            'content' => '<p>Introduction to PHP</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->blogPostService->getPublishedBlogPosts(['search' => 'Laravel']);
        
        expect($result->total())->toBe(1);
        expect($result->items()[0]->title)->toBe('Laravel Tutorial');
    });
    
    test('filters by category', function () {
        $category1 = Category::create(['name' => 'Web Dev', 'slug' => 'web-dev']);
        $category2 = Category::create(['name' => 'Mobile', 'slug' => 'mobile']);
        
        BlogPost::factory()->count(2)->create([
            'category_id' => $category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        BlogPost::factory()->create([
            'category_id' => $category2->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->blogPostService->getPublishedBlogPosts(['category_id' => $category1->id]);
        
        expect($result->total())->toBe(2);
        foreach ($result->items() as $post) {
            expect($post->category_id)->toBe($category1->id);
        }
    });
    
    test('filters by tag', function () {
        $tag1 = Tag::create(['name' => 'Laravel', 'slug' => 'laravel']);
        $tag2 = Tag::create(['name' => 'Vue', 'slug' => 'vue']);
        
        $post1 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post1->tags()->attach($tag1);
        
        $post2 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post2->tags()->attach($tag2);
        
        $result = $this->blogPostService->getPublishedBlogPosts(['tag_id' => $tag1->id]);
        
        expect($result->total())->toBe(1);
        expect($result->items()[0]->id)->toBe($post1->id);
    });
    
    test('eager loads relationships', function () {
        $post = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $tag = Tag::create(['name' => 'Test Tag', 'slug' => 'test-tag']);
        $post->tags()->attach($tag);
        
        $result = $this->blogPostService->getPublishedBlogPosts();
        
        $firstPost = $result->items()[0];
        expect($firstPost->relationLoaded('student'))->toBeTrue();
        expect($firstPost->relationLoaded('category'))->toBeTrue();
        expect($firstPost->relationLoaded('tags'))->toBeTrue();
    });
    
    test('includes comment and reaction counts', function () {
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->blogPostService->getPublishedBlogPosts();
        
        $firstPost = $result->items()[0];
        expect(isset($firstPost->comments_count))->toBeTrue();
        expect(isset($firstPost->reactions_count))->toBeTrue();
    });
    
    test('paginates results with default page size', function () {
        BlogPost::factory()->count(20)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->blogPostService->getPublishedBlogPosts();
        
        expect($result->perPage())->toBe(15);
        expect($result->count())->toBe(15);
        expect($result->total())->toBe(20);
    });
    
    test('paginates results with custom page size', function () {
        BlogPost::factory()->count(20)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->blogPostService->getPublishedBlogPosts([], 10);
        
        expect($result->perPage())->toBe(10);
        expect($result->count())->toBe(10);
    });
    
    test('caps page size at maximum of 100', function () {
        BlogPost::factory()->count(150)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->blogPostService->getPublishedBlogPosts([], 200);
        
        expect($result->perPage())->toBe(100);
    });
});

describe('getStudentBlogPosts', function () {
    test('returns all posts by student including drafts', function () {
        $student = Students::factory()->create();
        
        // Create posts by this student
        BlogPost::factory()->count(2)->create([
            'student_id' => $student->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        BlogPost::factory()->count(3)->create([
            'student_id' => $student->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        // Create posts by other students
        BlogPost::factory()->count(2)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->blogPostService->getStudentBlogPosts($student->id);
        
        expect($result->total())->toBe(5);
        foreach ($result->items() as $post) {
            expect($post->student_id)->toBe($student->id);
        }
    });
    
    test('orders posts by creation date descending', function () {
        $student = Students::factory()->create();
        
        $post1 = BlogPost::factory()->create([
            'student_id' => $student->id,
            'created_at' => now()->subDays(3),
        ]);
        $post2 = BlogPost::factory()->create([
            'student_id' => $student->id,
            'created_at' => now()->subDays(1),
        ]);
        $post3 = BlogPost::factory()->create([
            'student_id' => $student->id,
            'created_at' => now()->subDays(2),
        ]);
        
        $result = $this->blogPostService->getStudentBlogPosts($student->id);
        
        expect($result->items()[0]->id)->toBe($post2->id); // Most recent
        expect($result->items()[1]->id)->toBe($post3->id);
        expect($result->items()[2]->id)->toBe($post1->id); // Oldest
    });
    
    test('eager loads relationships', function () {
        $student = Students::factory()->create();
        $post = BlogPost::factory()->create([
            'student_id' => $student->id,
            'category_id' => $this->category->id,
        ]);
        $tag = Tag::create(['name' => 'Test Tag', 'slug' => 'test-tag']);
        $post->tags()->attach($tag);
        
        $result = $this->blogPostService->getStudentBlogPosts($student->id);
        
        $firstPost = $result->items()[0];
        expect($firstPost->relationLoaded('student'))->toBeTrue();
        expect($firstPost->relationLoaded('category'))->toBeTrue();
        expect($firstPost->relationLoaded('tags'))->toBeTrue();
    });
    
    test('includes comment and reaction counts', function () {
        $student = Students::factory()->create();
        BlogPost::factory()->create(['student_id' => $student->id]);
        
        $result = $this->blogPostService->getStudentBlogPosts($student->id);
        
        $firstPost = $result->items()[0];
        expect(isset($firstPost->comments_count))->toBeTrue();
        expect(isset($firstPost->reactions_count))->toBeTrue();
    });
    
    test('paginates results with default page size', function () {
        $student = Students::factory()->create();
        BlogPost::factory()->count(20)->create(['student_id' => $student->id]);
        
        $result = $this->blogPostService->getStudentBlogPosts($student->id);
        
        expect($result->perPage())->toBe(15);
        expect($result->count())->toBe(15);
        expect($result->total())->toBe(20);
    });
    
    test('paginates results with custom page size', function () {
        $student = Students::factory()->create();
        BlogPost::factory()->count(20)->create(['student_id' => $student->id]);
        
        $result = $this->blogPostService->getStudentBlogPosts($student->id, 10);
        
        expect($result->perPage())->toBe(10);
        expect($result->count())->toBe(10);
    });
    
    test('caps page size at maximum of 100', function () {
        $student = Students::factory()->create();
        BlogPost::factory()->count(150)->create(['student_id' => $student->id]);
        
        $result = $this->blogPostService->getStudentBlogPosts($student->id, 200);
        
        expect($result->perPage())->toBe(100);
    });
    
    test('returns empty result for student with no posts', function () {
        $student = Students::factory()->create();
        
        $result = $this->blogPostService->getStudentBlogPosts($student->id);
        
        expect($result->total())->toBe(0);
        expect($result->items())->toBeEmpty();
    });
});

describe('getBlogPost', function () {
    test('returns published blog post for any student', function () {
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $otherStudent = Students::factory()->create();
        
        $result = $this->blogPostService->getBlogPost($post->id, $otherStudent);
        
        expect($result->id)->toBe($post->id);
    });
    
    test('returns draft blog post for author', function () {
        $post = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        $result = $this->blogPostService->getBlogPost($post->id, $this->student);
        
        expect($result->id)->toBe($post->id);
    });
    
    test('throws authorization exception for draft post viewed by non-author', function () {
        $post = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        $otherStudent = Students::factory()->create();
        
        expect(fn() => $this->blogPostService->getBlogPost($post->id, $otherStudent))
            ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    });
    
    test('throws authorization exception when no student provided for draft post', function () {
        $post = BlogPost::factory()->create([
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        expect(fn() => $this->blogPostService->getBlogPost($post->id, null))
            ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    });
    
    test('eager loads relationships', function () {
        $post = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $tag = Tag::create(['name' => 'Test Tag', 'slug' => 'test-tag']);
        $post->tags()->attach($tag);
        
        $result = $this->blogPostService->getBlogPost($post->id, $this->student);
        
        expect($result->relationLoaded('student'))->toBeTrue();
        expect($result->relationLoaded('category'))->toBeTrue();
        expect($result->relationLoaded('tags'))->toBeTrue();
    });
    
    test('includes comment and reaction counts', function () {
        $post = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->blogPostService->getBlogPost($post->id, $this->student);
        
        expect(isset($result->comments_count))->toBeTrue();
        expect(isset($result->reactions_count))->toBeTrue();
    });
    
    test('throws exception for non-existent blog post', function () {
        expect(fn() => $this->blogPostService->getBlogPost(99999, $this->student))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });
});

describe('publishBlogPost', function () {
    test('publishes a draft blog post', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        $result = $this->blogPostService->publishBlogPost($blogPost);
        
        expect($result->status)->toBe('published');
        expect($result->published_at)->not->toBeNull();
    });
    
    test('sets published_at timestamp when publishing', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        $beforePublish = now()->subSecond();
        $result = $this->blogPostService->publishBlogPost($blogPost);
        $afterPublish = now()->addSecond();
        
        expect($result->published_at)->not->toBeNull();
        expect($result->published_at->between($beforePublish, $afterPublish))->toBeTrue();
    });
    
    test('can publish an already published post', function () {
        $originalPublishedAt = now()->subDays(5);
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'published',
            'published_at' => $originalPublishedAt,
        ]);
        
        $result = $this->blogPostService->publishBlogPost($blogPost);
        
        expect($result->status)->toBe('published');
        expect($result->published_at)->not->toBeNull();
        // Published_at should be updated to now
        expect($result->published_at->greaterThan($originalPublishedAt))->toBeTrue();
    });
    
    test('persists changes to database', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        $this->blogPostService->publishBlogPost($blogPost);
        
        // Refresh from database
        $blogPost->refresh();
        
        expect($blogPost->status)->toBe('published');
        expect($blogPost->published_at)->not->toBeNull();
    });
});

describe('unpublishBlogPost', function () {
    test('unpublishes a published blog post', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result = $this->blogPostService->unpublishBlogPost($blogPost);
        
        expect($result->status)->toBe('draft');
        expect($result->published_at)->toBeNull();
    });
    
    test('clears published_at timestamp when unpublishing', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);
        
        $result = $this->blogPostService->unpublishBlogPost($blogPost);
        
        expect($result->published_at)->toBeNull();
    });
    
    test('can unpublish an already draft post', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'draft',
            'published_at' => null,
        ]);
        
        $result = $this->blogPostService->unpublishBlogPost($blogPost);
        
        expect($result->status)->toBe('draft');
        expect($result->published_at)->toBeNull();
    });
    
    test('persists changes to database', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $this->blogPostService->unpublishBlogPost($blogPost);
        
        // Refresh from database
        $blogPost->refresh();
        
        expect($blogPost->status)->toBe('draft');
        expect($blogPost->published_at)->toBeNull();
    });
    
    test('makes post invisible to other students', function () {
        $blogPost = BlogPost::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        // Verify it's visible in published posts before unpublishing
        $publishedPosts = $this->blogPostService->getPublishedBlogPosts();
        expect($publishedPosts->total())->toBe(1);
        
        // Unpublish the post
        $this->blogPostService->unpublishBlogPost($blogPost);
        
        // Verify it's no longer visible in published posts
        $publishedPosts = $this->blogPostService->getPublishedBlogPosts();
        expect($publishedPosts->total())->toBe(0);
    });
});
