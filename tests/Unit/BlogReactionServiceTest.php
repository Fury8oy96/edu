<?php

use App\Models\BlogPost;
use App\Models\BlogReaction;
use App\Models\Students;
use App\Services\BlogReactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create service
    $this->reactionService = new BlogReactionService();
    
    // Create test students
    $this->student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $this->anotherStudent = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Create a published blog post
    $this->blogPost = BlogPost::factory()->create([
        'status' => 'published',
        'published_at' => now(),
    ]);
});

describe('toggleReaction', function () {
    test('adds reaction when it does not exist', function () {
        $result = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        expect($result)->toBeArray();
        expect($result['action'])->toBe('added');
        expect($result['total_reactions'])->toBe(1);
        
        // Verify database record
        $this->assertDatabaseHas('blog_reactions', [
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
    });
    
    test('removes reaction when it already exists', function () {
        // First, add a reaction
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        
        // Now toggle it (should remove)
        $result = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        expect($result['action'])->toBe('removed');
        expect($result['total_reactions'])->toBe(0);
        
        // Verify database record is gone
        $this->assertDatabaseMissing('blog_reactions', [
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
    });
    
    test('toggle behavior works correctly - add, remove, add', function () {
        // First toggle - add
        $result1 = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        expect($result1['action'])->toBe('added');
        expect($result1['total_reactions'])->toBe(1);
        
        // Second toggle - remove
        $result2 = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        expect($result2['action'])->toBe('removed');
        expect($result2['total_reactions'])->toBe(0);
        
        // Third toggle - add again
        $result3 = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        expect($result3['action'])->toBe('added');
        expect($result3['total_reactions'])->toBe(1);
    });
    
    test('returns correct total reactions count with multiple students', function () {
        // Add reaction from first student
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        // Add reaction from second student
        $result = $this->reactionService->toggleReaction($this->blogPost, $this->anotherStudent);
        
        expect($result['action'])->toBe('added');
        expect($result['total_reactions'])->toBe(2);
    });
    
    test('removing one reaction does not affect others', function () {
        // Add reactions from both students
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        $this->reactionService->toggleReaction($this->blogPost, $this->anotherStudent);
        
        // Remove first student's reaction
        $result = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        expect($result['action'])->toBe('removed');
        expect($result['total_reactions'])->toBe(1);
        
        // Verify second student's reaction still exists
        $this->assertDatabaseHas('blog_reactions', [
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->anotherStudent->id,
        ]);
    });
    
    test('creates reaction with correct associations', function () {
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        $reaction = BlogReaction::where('blog_post_id', $this->blogPost->id)
            ->where('student_id', $this->student->id)
            ->first();
        
        expect($reaction)->not->toBeNull();
        expect($reaction->blog_post_id)->toBe($this->blogPost->id);
        expect($reaction->student_id)->toBe($this->student->id);
        expect($reaction->created_at)->not->toBeNull();
    });
    
    test('different students can react to same post independently', function () {
        $result1 = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        $result2 = $this->reactionService->toggleReaction($this->blogPost, $this->anotherStudent);
        
        expect($result1['action'])->toBe('added');
        expect($result2['action'])->toBe('added');
        expect($result2['total_reactions'])->toBe(2);
        
        // Verify both reactions exist
        $this->assertDatabaseHas('blog_reactions', [
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        $this->assertDatabaseHas('blog_reactions', [
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->anotherStudent->id,
        ]);
    });
    
    test('same student can react to different posts', function () {
        $blogPost2 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        $result1 = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        $result2 = $this->reactionService->toggleReaction($blogPost2, $this->student);
        
        expect($result1['action'])->toBe('added');
        expect($result2['action'])->toBe('added');
        
        // Verify both reactions exist
        $this->assertDatabaseHas('blog_reactions', [
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        $this->assertDatabaseHas('blog_reactions', [
            'blog_post_id' => $blogPost2->id,
            'student_id' => $this->student->id,
        ]);
    });
});

describe('hasReacted', function () {
    test('returns true when student has reacted', function () {
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        
        $result = $this->reactionService->hasReacted($this->blogPost->id, $this->student->id);
        
        expect($result)->toBeTrue();
    });
    
    test('returns false when student has not reacted', function () {
        $result = $this->reactionService->hasReacted($this->blogPost->id, $this->student->id);
        
        expect($result)->toBeFalse();
    });
    
    test('returns false for different student', function () {
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        
        $result = $this->reactionService->hasReacted($this->blogPost->id, $this->anotherStudent->id);
        
        expect($result)->toBeFalse();
    });
    
    test('returns false for different blog post', function () {
        $blogPost2 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        
        $result = $this->reactionService->hasReacted($blogPost2->id, $this->student->id);
        
        expect($result)->toBeFalse();
    });
    
    test('returns true after adding reaction via toggle', function () {
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        $result = $this->reactionService->hasReacted($this->blogPost->id, $this->student->id);
        
        expect($result)->toBeTrue();
    });
    
    test('returns false after removing reaction via toggle', function () {
        // Add reaction
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        // Remove reaction
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        $result = $this->reactionService->hasReacted($this->blogPost->id, $this->student->id);
        
        expect($result)->toBeFalse();
    });
    
    test('correctly identifies reactions for multiple students', function () {
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        
        $hasReacted1 = $this->reactionService->hasReacted($this->blogPost->id, $this->student->id);
        $hasReacted2 = $this->reactionService->hasReacted($this->blogPost->id, $this->anotherStudent->id);
        
        expect($hasReacted1)->toBeTrue();
        expect($hasReacted2)->toBeFalse();
    });
});

describe('getReactionCount', function () {
    test('returns zero when no reactions exist', function () {
        $count = $this->reactionService->getReactionCount($this->blogPost->id);
        
        expect($count)->toBe(0);
    });
    
    test('returns correct count with one reaction', function () {
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        
        $count = $this->reactionService->getReactionCount($this->blogPost->id);
        
        expect($count)->toBe(1);
    });
    
    test('returns correct count with multiple reactions', function () {
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->anotherStudent->id,
        ]);
        
        $count = $this->reactionService->getReactionCount($this->blogPost->id);
        
        expect($count)->toBe(2);
    });
    
    test('returns correct count after adding reaction via toggle', function () {
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        $count = $this->reactionService->getReactionCount($this->blogPost->id);
        
        expect($count)->toBe(1);
    });
    
    test('returns correct count after removing reaction via toggle', function () {
        // Add reaction
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        // Remove reaction
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        
        $count = $this->reactionService->getReactionCount($this->blogPost->id);
        
        expect($count)->toBe(0);
    });
    
    test('counts only reactions for specified blog post', function () {
        $blogPost2 = BlogPost::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        // Add reactions to first post
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->student->id,
        ]);
        BlogReaction::create([
            'blog_post_id' => $this->blogPost->id,
            'student_id' => $this->anotherStudent->id,
        ]);
        
        // Add reaction to second post
        BlogReaction::create([
            'blog_post_id' => $blogPost2->id,
            'student_id' => $this->student->id,
        ]);
        
        $count1 = $this->reactionService->getReactionCount($this->blogPost->id);
        $count2 = $this->reactionService->getReactionCount($blogPost2->id);
        
        expect($count1)->toBe(2);
        expect($count2)->toBe(1);
    });
    
    test('returns correct count with many reactions', function () {
        $students = Students::factory()->count(10)->create([
            'email_verified_at' => now(),
        ]);
        
        foreach ($students as $student) {
            BlogReaction::create([
                'blog_post_id' => $this->blogPost->id,
                'student_id' => $student->id,
            ]);
        }
        
        $count = $this->reactionService->getReactionCount($this->blogPost->id);
        
        expect($count)->toBe(10);
    });
    
    test('count updates correctly after multiple toggles', function () {
        // Add reactions from both students
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        $this->reactionService->toggleReaction($this->blogPost, $this->anotherStudent);
        expect($this->reactionService->getReactionCount($this->blogPost->id))->toBe(2);
        
        // Remove one reaction
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        expect($this->reactionService->getReactionCount($this->blogPost->id))->toBe(1);
        
        // Add it back
        $this->reactionService->toggleReaction($this->blogPost, $this->student);
        expect($this->reactionService->getReactionCount($this->blogPost->id))->toBe(2);
    });
});

describe('integration tests', function () {
    test('complete reaction workflow for single student', function () {
        // Initially no reactions
        expect($this->reactionService->hasReacted($this->blogPost->id, $this->student->id))->toBeFalse();
        expect($this->reactionService->getReactionCount($this->blogPost->id))->toBe(0);
        
        // Add reaction
        $result = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        expect($result['action'])->toBe('added');
        expect($result['total_reactions'])->toBe(1);
        expect($this->reactionService->hasReacted($this->blogPost->id, $this->student->id))->toBeTrue();
        
        // Remove reaction
        $result = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        expect($result['action'])->toBe('removed');
        expect($result['total_reactions'])->toBe(0);
        expect($this->reactionService->hasReacted($this->blogPost->id, $this->student->id))->toBeFalse();
    });
    
    test('complete reaction workflow for multiple students', function () {
        // Student 1 adds reaction
        $result1 = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        expect($result1['action'])->toBe('added');
        expect($result1['total_reactions'])->toBe(1);
        
        // Student 2 adds reaction
        $result2 = $this->reactionService->toggleReaction($this->blogPost, $this->anotherStudent);
        expect($result2['action'])->toBe('added');
        expect($result2['total_reactions'])->toBe(2);
        
        // Verify both have reacted
        expect($this->reactionService->hasReacted($this->blogPost->id, $this->student->id))->toBeTrue();
        expect($this->reactionService->hasReacted($this->blogPost->id, $this->anotherStudent->id))->toBeTrue();
        
        // Student 1 removes reaction
        $result3 = $this->reactionService->toggleReaction($this->blogPost, $this->student);
        expect($result3['action'])->toBe('removed');
        expect($result3['total_reactions'])->toBe(1);
        
        // Verify only student 2 has reacted
        expect($this->reactionService->hasReacted($this->blogPost->id, $this->student->id))->toBeFalse();
        expect($this->reactionService->hasReacted($this->blogPost->id, $this->anotherStudent->id))->toBeTrue();
    });
});
