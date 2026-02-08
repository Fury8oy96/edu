<?php

use App\Models\BlogPost;
use App\Models\BlogComment;
use App\Models\BlogReaction;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Integration Test: Complete blog post lifecycle
 * Flow: create post → publish → comment → react → delete
 */
test('complete blog post lifecycle: create → publish → comment → react → delete', function () {
    // Create two verified students
    $author = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $commenter = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Step 1: Create a draft blog post
    $createResponse = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/blog-posts', [
            'title' => 'My Learning Journey',
            'content' => 'This is a detailed post about my learning journey in web development.',
            'status' => 'draft',
        ]);
    
    $createResponse->assertStatus(201);
    $createResponse->assertJsonFragment(['status' => 'draft']);
    $postId = $createResponse->json('data.id');
    
    // Verify draft is not visible to other students
    $draftViewResponse = $this->actingAs($commenter, 'sanctum')
        ->getJson("/api/v1/blog-posts/{$postId}");
    $draftViewResponse->assertStatus(403);
    
    // Step 2: Publish the blog post
    $publishResponse = $this->actingAs($author, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$postId}/publish");
    
    $publishResponse->assertStatus(200);
    $publishResponse->assertJsonFragment(['status' => 'published']);
    expect($publishResponse->json('data.published_at'))->not->toBeNull();
    
    // Verify published post is now visible to other students
    $publishedViewResponse = $this->actingAs($commenter, 'sanctum')
        ->getJson("/api/v1/blog-posts/{$postId}");
    $publishedViewResponse->assertStatus(200);
    
    // Step 3: Add a comment
    $commentResponse = $this->actingAs($commenter, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$postId}/comments", [
            'content' => 'Great post! Very insightful.',
        ]);
    
    $commentResponse->assertStatus(201);
    $commentResponse->assertJsonFragment(['content' => 'Great post! Very insightful.']);
    $commentId = $commentResponse->json('data.id');
    
    // Verify comment appears in post
    $postWithCommentResponse = $this->actingAs($author, 'sanctum')
        ->getJson("/api/v1/blog-posts/{$postId}");
    $postWithCommentResponse->assertStatus(200);
    expect($postWithCommentResponse->json('data.comments_count'))->toBe(1);
    
    // Step 4: Add a reaction
    $reactionResponse = $this->actingAs($commenter, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$postId}/reactions");
    
    $reactionResponse->assertStatus(200);
    $reactionResponse->assertJsonFragment(['action' => 'added']);
    expect($reactionResponse->json('total_reactions'))->toBe(1);
    
    // Verify reaction count in post
    $postWithReactionResponse = $this->actingAs($author, 'sanctum')
        ->getJson("/api/v1/blog-posts/{$postId}");
    $postWithReactionResponse->assertStatus(200);
    expect($postWithReactionResponse->json('data.reactions_count'))->toBe(1);
    
    // Step 5: Delete the blog post
    $deleteResponse = $this->actingAs($author, 'sanctum')
        ->deleteJson("/api/v1/blog-posts/{$postId}");
    
    $deleteResponse->assertStatus(204);
    
    // Verify post, comment, and reaction are all deleted (cascade)
    $this->assertDatabaseMissing('blog_posts', ['id' => $postId]);
    $this->assertDatabaseMissing('blog_comments', ['id' => $commentId]);
    $this->assertDatabaseMissing('blog_reactions', [
        'blog_post_id' => $postId,
        'student_id' => $commenter->id,
    ]);
});

/**
 * Integration Test: Authorization flows
 * Test unauthorized actions are properly rejected
 */
test('authorization flows: unauthorized actions are rejected', function () {
    // Create two verified students
    $author = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $otherStudent = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a published blog post
    $blogPost = BlogPost::factory()->published()->create([
        'student_id' => $author->id,
    ]);
    
    // Create a comment by the author
    $comment = BlogComment::factory()->create([
        'blog_post_id' => $blogPost->id,
        'student_id' => $author->id,
        'content' => 'My own comment',
    ]);
    
    // Test 1: Other student cannot edit the post
    $editResponse = $this->actingAs($otherStudent, 'sanctum')
        ->putJson("/api/v1/blog-posts/{$blogPost->id}", [
            'title' => 'Unauthorized Edit',
        ]);
    $editResponse->assertStatus(403);
    
    // Test 2: Other student cannot delete the post
    $deleteResponse = $this->actingAs($otherStudent, 'sanctum')
        ->deleteJson("/api/v1/blog-posts/{$blogPost->id}");
    $deleteResponse->assertStatus(403);
    
    // Test 3: Other student cannot publish/unpublish the post
    $unpublishResponse = $this->actingAs($otherStudent, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/unpublish");
    $unpublishResponse->assertStatus(403);
    
    // Test 4: Other student cannot delete author's comment
    $deleteCommentResponse = $this->actingAs($otherStudent, 'sanctum')
        ->deleteJson("/api/v1/blog-comments/{$comment->id}");
    $deleteCommentResponse->assertStatus(403);
    
    // Clear authentication for unauthenticated tests
    $this->app['auth']->forgetGuards();
    
    // Test 5: Unauthenticated user cannot create post
    $createResponse = $this->postJson('/api/v1/blog-posts', [
        'title' => 'Unauthorized Post',
        'content' => 'This should fail',
    ]);
    $createResponse->assertStatus(401);
    
    // Test 6: Unauthenticated user cannot comment
    $commentResponse = $this->postJson("/api/v1/blog-posts/{$blogPost->id}/comments", [
        'content' => 'Unauthorized comment',
    ]);
    $commentResponse->assertStatus(401);
    
    // Test 7: Unauthenticated user cannot react
    $reactionResponse = $this->postJson("/api/v1/blog-posts/{$blogPost->id}/reactions");
    $reactionResponse->assertStatus(401);
});

/**
 * Integration Test: Validation flows
 * Test invalid data is properly rejected with validation errors
 */
test('validation flows: invalid data is rejected with proper errors', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Test 1: Blog post with title too short
    $shortTitleResponse = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', [
            'title' => 'AB', // Less than 3 characters
            'content' => 'Valid content here',
        ]);
    $shortTitleResponse->assertStatus(422);
    $shortTitleResponse->assertJsonValidationErrors(['title']);
    
    // Test 2: Blog post with title too long
    $longTitleResponse = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', [
            'title' => str_repeat('A', 201), // More than 200 characters
            'content' => 'Valid content here',
        ]);
    $longTitleResponse->assertStatus(422);
    $longTitleResponse->assertJsonValidationErrors(['title']);
    
    // Test 3: Blog post with content too short
    $shortContentResponse = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', [
            'title' => 'Valid Title',
            'content' => 'Short', // Less than 10 characters
        ]);
    $shortContentResponse->assertStatus(422);
    $shortContentResponse->assertJsonValidationErrors(['content']);
    
    // Test 4: Blog post with missing required fields
    $missingFieldsResponse = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', []);
    $missingFieldsResponse->assertStatus(422);
    $missingFieldsResponse->assertJsonValidationErrors(['title', 'content']);
    
    // Test 5: Blog post with invalid status
    $invalidStatusResponse = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', [
            'title' => 'Valid Title',
            'content' => 'Valid content here',
            'status' => 'invalid_status',
        ]);
    $invalidStatusResponse->assertStatus(422);
    $invalidStatusResponse->assertJsonValidationErrors(['status']);
    
    // Create a published post for comment tests
    $blogPost = BlogPost::factory()->published()->create([
        'student_id' => $student->id,
    ]);
    
    // Test 6: Comment with empty content
    $emptyCommentResponse = $this->actingAs($student, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/comments", [
            'content' => '',
        ]);
    $emptyCommentResponse->assertStatus(422);
    $emptyCommentResponse->assertJsonValidationErrors(['content']);
    
    // Test 7: Comment with content too long
    $longCommentResponse = $this->actingAs($student, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/comments", [
            'content' => str_repeat('A', 1001), // More than 1000 characters
        ]);
    $longCommentResponse->assertStatus(422);
    $longCommentResponse->assertJsonValidationErrors(['content']);
    
    // Test 8: Invalid image format
    $invalidImageResponse = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', [
            'title' => 'Valid Title',
            'content' => 'Valid content here',
            'featured_image' => UploadedFile::fake()->create('document.pdf', 100),
        ]);
    $invalidImageResponse->assertStatus(422);
    $invalidImageResponse->assertJsonValidationErrors(['featured_image']);
    
    // Test 9: Image too large
    $largeImageResponse = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', [
            'title' => 'Valid Title',
            'content' => 'Valid content here',
            'featured_image' => UploadedFile::fake()->image('large.jpg')->size(6000), // More than 5MB
        ]);
    $largeImageResponse->assertStatus(422);
    $largeImageResponse->assertJsonValidationErrors(['featured_image']);
});

/**
 * Integration Test: Draft post restrictions
 * Test that draft posts cannot receive comments or reactions
 */
test('draft posts cannot receive comments or reactions', function () {
    // Create two verified students
    $author = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $otherStudent = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a draft blog post
    $draftPost = BlogPost::factory()->draft()->create([
        'student_id' => $author->id,
    ]);
    
    // Test 1: Cannot comment on draft post
    $commentResponse = $this->actingAs($otherStudent, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$draftPost->id}/comments", [
            'content' => 'This should fail',
        ]);
    $commentResponse->assertStatus(403);
    
    // Test 2: Cannot react to draft post
    $reactionResponse = $this->actingAs($otherStudent, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$draftPost->id}/reactions");
    $reactionResponse->assertStatus(403);
    
    // Test 3: Author also cannot comment on their own draft
    $authorCommentResponse = $this->actingAs($author, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$draftPost->id}/comments", [
            'content' => 'Even author cannot comment on draft',
        ]);
    $authorCommentResponse->assertStatus(403);
});

/**
 * Integration Test: Reaction toggle behavior
 * Test that reactions can be toggled on and off
 */
test('reaction toggle behavior works correctly', function () {
    // Create two verified students
    $author = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $reactor = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a published blog post
    $blogPost = BlogPost::factory()->published()->create([
        'student_id' => $author->id,
    ]);
    
    // First toggle: Add reaction
    $addResponse = $this->actingAs($reactor, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/reactions");
    
    $addResponse->assertStatus(200);
    $addResponse->assertJsonFragment(['action' => 'added']);
    expect($addResponse->json('total_reactions'))->toBe(1);
    
    // Verify reaction exists in database
    $this->assertDatabaseHas('blog_reactions', [
        'blog_post_id' => $blogPost->id,
        'student_id' => $reactor->id,
    ]);
    
    // Second toggle: Remove reaction
    $removeResponse = $this->actingAs($reactor, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/reactions");
    
    $removeResponse->assertStatus(200);
    $removeResponse->assertJsonFragment(['action' => 'removed']);
    expect($removeResponse->json('total_reactions'))->toBe(0);
    
    // Verify reaction is removed from database
    $this->assertDatabaseMissing('blog_reactions', [
        'blog_post_id' => $blogPost->id,
        'student_id' => $reactor->id,
    ]);
    
    // Third toggle: Add reaction again
    $addAgainResponse = $this->actingAs($reactor, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/reactions");
    
    $addAgainResponse->assertStatus(200);
    $addAgainResponse->assertJsonFragment(['action' => 'added']);
    expect($addAgainResponse->json('total_reactions'))->toBe(1);
});

/**
 * Integration Test: Email verification requirement
 * Test that unverified students cannot create or interact with blog content
 */
test('unverified students cannot create or interact with blog content', function () {
    // Create an unverified student
    $unverifiedStudent = Students::factory()->create([
        'email_verified_at' => null,
    ]);
    
    // Create a verified student and a published post
    $verifiedStudent = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $blogPost = BlogPost::factory()->published()->create([
        'student_id' => $verifiedStudent->id,
    ]);
    
    // Test 1: Cannot create blog post
    $createResponse = $this->actingAs($unverifiedStudent, 'sanctum')
        ->postJson('/api/v1/blog-posts', [
            'title' => 'Test Post',
            'content' => 'This should fail',
        ]);
    $createResponse->assertStatus(403);
    
    // Test 2: Cannot comment
    $commentResponse = $this->actingAs($unverifiedStudent, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/comments", [
            'content' => 'This should fail',
        ]);
    $commentResponse->assertStatus(403);
    
    // Test 3: Cannot react
    $reactionResponse = $this->actingAs($unverifiedStudent, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/reactions");
    $reactionResponse->assertStatus(403);
});

/**
 * Integration Test: Multiple comments and reactions
 * Test that multiple students can comment and react to the same post
 */
test('multiple students can comment and react to the same post', function () {
    // Create author and multiple other students
    $author = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $students = Students::factory()->count(3)->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a published blog post
    $blogPost = BlogPost::factory()->published()->create([
        'student_id' => $author->id,
    ]);
    
    // Each student adds a comment
    foreach ($students as $index => $student) {
        $commentResponse = $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/blog-posts/{$blogPost->id}/comments", [
                'content' => "Comment from student {$index}",
            ]);
        $commentResponse->assertStatus(201);
    }
    
    // Each student adds a reaction
    foreach ($students as $student) {
        $reactionResponse = $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/blog-posts/{$blogPost->id}/reactions");
        $reactionResponse->assertStatus(200);
    }
    
    // Verify counts
    $postResponse = $this->actingAs($author, 'sanctum')
        ->getJson("/api/v1/blog-posts/{$blogPost->id}");
    
    $postResponse->assertStatus(200);
    expect($postResponse->json('data.comments_count'))->toBe(3);
    expect($postResponse->json('data.reactions_count'))->toBe(3);
    
    // Verify comments are returned in order
    $commentsResponse = $this->getJson("/api/v1/blog-posts/{$blogPost->id}/comments");
    $commentsResponse->assertStatus(200);
    $commentsResponse->assertJsonCount(3, 'data');
});

/**
 * Integration Test: Cascade deletion with featured image
 * Test that deleting a post with featured image removes the image file
 */
test('deleting post with featured image removes the image file', function () {
    // Create a verified student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a fake image
    $image = UploadedFile::fake()->image('test.jpg', 800, 600);
    
    // Create a blog post with featured image
    $createResponse = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/blog-posts', [
            'title' => 'Post with Image',
            'content' => 'This post has a featured image.',
            'featured_image' => $image,
        ]);
    
    $createResponse->assertStatus(201);
    $postId = $createResponse->json('data.id');
    $featuredImage = $createResponse->json('data.featured_image');
    
    // Extract the path from the URL
    $path = str_replace('/storage/', '', parse_url($featuredImage, PHP_URL_PATH));
    
    // Verify image exists
    Storage::disk('public')->assertExists($path);
    
    // Delete the blog post
    $deleteResponse = $this->actingAs($student, 'sanctum')
        ->deleteJson("/api/v1/blog-posts/{$postId}");
    
    $deleteResponse->assertStatus(204);
    
    // Verify image is deleted
    Storage::disk('public')->assertMissing($path);
});

/**
 * Integration Test: Comment deletion by author
 * Test that comment authors can delete their own comments
 */
test('comment authors can delete their own comments', function () {
    // Create two verified students
    $postAuthor = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $commenter = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a published blog post
    $blogPost = BlogPost::factory()->published()->create([
        'student_id' => $postAuthor->id,
    ]);
    
    // Add a comment
    $commentResponse = $this->actingAs($commenter, 'sanctum')
        ->postJson("/api/v1/blog-posts/{$blogPost->id}/comments", [
            'content' => 'This is my comment',
        ]);
    
    $commentResponse->assertStatus(201);
    $commentId = $commentResponse->json('data.id');
    
    // Verify comment exists
    $this->assertDatabaseHas('blog_comments', [
        'id' => $commentId,
        'student_id' => $commenter->id,
    ]);
    
    // Delete the comment as the commenter
    $deleteResponse = $this->actingAs($commenter, 'sanctum')
        ->deleteJson("/api/v1/blog-comments/{$commentId}");
    
    $deleteResponse->assertStatus(204);
    
    // Verify comment is deleted
    $this->assertDatabaseMissing('blog_comments', [
        'id' => $commentId,
    ]);
});
