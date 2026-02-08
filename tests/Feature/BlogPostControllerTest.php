<?php

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('index returns published blog posts with pagination', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create published and draft blog posts
    $publishedPost = BlogPost::factory()->published()->create([
        'student_id' => $student->id,
    ]);
    
    $draftPost = BlogPost::factory()->draft()->create([
        'student_id' => $student->id,
    ]);
    
    // Make request to index endpoint
    $response = $this->getJson('/api/v1/blog-posts');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response structure includes pagination
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'title',
                'slug',
                'content',
                'excerpt',
                'status',
                'published_at',
                'author' => ['id', 'name'],
            ],
        ],
        'links',
        'meta',
    ]);
    
    // Assert only published post is returned
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['id' => $publishedPost->id]);
    $response->assertJsonMissing(['id' => $draftPost->id]);
});

test('show returns blog post for authenticated user', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a published blog post
    $blogPost = BlogPost::factory()->published()->create([
        'student_id' => $student->id,
    ]);
    
    // Make authenticated request to show endpoint
    $response = $this->actingAs($student, 'sanctum')
        ->getJson("/api/v1/blog-posts/{$blogPost->id}");
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response contains blog post data
    $response->assertJsonFragment([
        'id' => $blogPost->id,
        'title' => $blogPost->title,
        'slug' => $blogPost->slug,
    ]);
});

test('myPosts returns authenticated student blog posts', function () {
    // Create verified students
    $student1 = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    $student2 = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create blog posts for both students
    $student1Post = BlogPost::factory()->published()->create([
        'student_id' => $student1->id,
    ]);
    
    $student1Draft = BlogPost::factory()->draft()->create([
        'student_id' => $student1->id,
    ]);
    
    $student2Post = BlogPost::factory()->published()->create([
        'student_id' => $student2->id,
    ]);
    
    // Make authenticated request as student1
    $response = $this->actingAs($student1, 'sanctum')
        ->getJson('/api/v1/blog-posts/my-posts');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response contains both published and draft posts for student1
    $response->assertJsonCount(2, 'data');
    $response->assertJsonFragment(['id' => $student1Post->id]);
    $response->assertJsonFragment(['id' => $student1Draft->id]);
    
    // Assert response does not contain student2's post
    $response->assertJsonMissing(['id' => $student2Post->id]);
});

test('store creates new blog post with 201 status', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Prepare blog post data
    $data = [
        'title' => 'Test Blog Post',
        'content' => 'This is the content of the test blog post.',
        'status' => 'draft',
    ];
    
    // Make authenticated request to store endpoint
    $response = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', $data);
    
    // Assert response status is 201 Created
    $response->assertStatus(201);
    
    // Assert response contains blog post data
    $response->assertJsonFragment([
        'title' => $data['title'],
        'content' => $data['content'],
        'status' => $data['status'],
    ]);
    
    // Assert blog post was created in database
    $this->assertDatabaseHas('blog_posts', [
        'title' => $data['title'],
        'content' => $data['content'],
        'student_id' => $student->id,
    ]);
});

test('store handles image upload', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a fake image
    $image = UploadedFile::fake()->image('test.jpg', 800, 600);
    
    // Prepare blog post data with image
    $data = [
        'title' => 'Test Blog Post with Image',
        'content' => 'This is the content of the test blog post.',
        'status' => 'draft',
        'featured_image' => $image,
    ];
    
    // Make authenticated request to store endpoint
    $response = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', $data);
    
    // Assert response status is 201 Created
    $response->assertStatus(201);
    
    // Assert response contains featured_image URL
    expect($response->json('data.featured_image'))->not->toBeNull();
    
    // Assert image was stored
    $featuredImage = $response->json('data.featured_image');
    $path = str_replace('/storage/', '', parse_url($featuredImage, PHP_URL_PATH));
    Storage::disk('public')->assertExists($path);
});

test('update modifies existing blog post', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post
    $blogPost = BlogPost::factory()->draft()->create([
        'student_id' => $student->id,
        'title' => 'Original Title',
    ]);
    
    // Prepare update data
    $data = [
        'title' => 'Updated Title',
    ];
    
    // Make authenticated request to update endpoint
    $response = $this->actingAs($student, 'sanctum')
        ->putJson("/api/v1/blog-posts/{$blogPost->id}", $data);
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response contains updated data
    $response->assertJsonFragment([
        'title' => $data['title'],
    ]);
    
    // Assert blog post was updated in database
    $this->assertDatabaseHas('blog_posts', [
        'id' => $blogPost->id,
        'title' => $data['title'],
    ]);
});

test('update requires authorization', function () {
    // Create two verified students
    $student1 = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    $student2 = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post for student1
    $blogPost = BlogPost::factory()->draft()->create([
        'student_id' => $student1->id,
    ]);
    
    // Prepare update data
    $data = [
        'title' => 'Unauthorized Update',
    ];
    
    // Make authenticated request as student2 (not the author)
    $response = $this->actingAs($student2, 'sanctum')
        ->putJson("/api/v1/blog-posts/{$blogPost->id}", $data);
    
    // Assert response status is 403 Forbidden
    $response->assertStatus(403);
});

test('destroy deletes blog post with 204 status', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post
    $blogPost = BlogPost::factory()->draft()->create([
        'student_id' => $student->id,
    ]);
    
    // Make authenticated request to destroy endpoint
    $response = $this->actingAs($student, 'sanctum')
        ->deleteJson("/api/v1/blog-posts/{$blogPost->id}");
    
    // Assert response status is 204 No Content
    $response->assertStatus(204);
    
    // Assert blog post was deleted from database
    $this->assertDatabaseMissing('blog_posts', [
        'id' => $blogPost->id,
    ]);
});

test('destroy requires authorization', function () {
    // Create two verified students
    $student1 = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    $student2 = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a blog post for student1
    $blogPost = BlogPost::factory()->draft()->create([
        'student_id' => $student1->id,
    ]);
    
    // Make authenticated request as student2 (not the author)
    $response = $this->actingAs($student2, 'sanctum')
        ->deleteJson("/api/v1/blog-posts/{$blogPost->id}");
    
    // Assert response status is 403 Forbidden
    $response->assertStatus(403);
});

test('publish changes blog post status to published', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a draft blog post
    $blogPost = BlogPost::factory()->draft()->create([
        'student_id' => $student->id,
    ]);
    
    // Make authenticated request to publish endpoint
    $response = $this->actingAs($student, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/publish");
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response contains published status
    $response->assertJsonFragment([
        'status' => 'published',
    ]);
    
    // Assert published_at is set
    expect($response->json('data.published_at'))->not->toBeNull();
    
    // Assert blog post was updated in database
    $this->assertDatabaseHas('blog_posts', [
        'id' => $blogPost->id,
        'status' => 'published',
    ]);
});

test('unpublish changes blog post status to draft', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a published blog post
    $blogPost = BlogPost::factory()->published()->create([
        'student_id' => $student->id,
    ]);
    
    // Make authenticated request to unpublish endpoint
    $response = $this->actingAs($student, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/unpublish");
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response contains draft status
    $response->assertJsonFragment([
        'status' => 'draft',
    ]);
    
    // Assert published_at is null
    expect($response->json('data.published_at'))->toBeNull();
    
    // Assert blog post was updated in database
    $this->assertDatabaseHas('blog_posts', [
        'id' => $blogPost->id,
        'status' => 'draft',
    ]);
});
