<?php

use App\Models\BlogPost;
use App\Models\Students;
use App\Policies\BlogPostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('BlogPostPolicy', function () {
    
    describe('view method', function () {
        
        it('allows anyone to view a published post', function () {
            $author = Students::factory()->create();
            $post = BlogPost::factory()->create([
                'student_id' => $author->id,
                'status' => 'published',
            ]);
            
            $policy = new BlogPostPolicy();
            
            // Guest (null) can view published post
            expect($policy->view(null, $post))->toBeTrue();
            
            // Another student can view published post
            $otherStudent = Students::factory()->create();
            expect($policy->view($otherStudent, $post))->toBeTrue();
            
            // Author can view their own published post
            expect($policy->view($author, $post))->toBeTrue();
        });
        
        it('allows only the author to view a draft post', function () {
            $author = Students::factory()->create();
            $post = BlogPost::factory()->create([
                'student_id' => $author->id,
                'status' => 'draft',
            ]);
            
            $policy = new BlogPostPolicy();
            
            // Guest (null) cannot view draft post
            expect($policy->view(null, $post))->toBeFalse();
            
            // Another student cannot view draft post
            $otherStudent = Students::factory()->create();
            expect($policy->view($otherStudent, $post))->toBeFalse();
            
            // Author can view their own draft post
            expect($policy->view($author, $post))->toBeTrue();
        });
        
    });
    
    describe('update method', function () {
        
        it('allows only the author to update their post', function () {
            $author = Students::factory()->create();
            $post = BlogPost::factory()->create([
                'student_id' => $author->id,
            ]);
            
            $policy = new BlogPostPolicy();
            
            // Author can update their own post
            expect($policy->update($author, $post))->toBeTrue();
            
            // Another student cannot update the post
            $otherStudent = Students::factory()->create();
            expect($policy->update($otherStudent, $post))->toBeFalse();
        });
        
        it('works for both published and draft posts', function () {
            $author = Students::factory()->create();
            $otherStudent = Students::factory()->create();
            
            $publishedPost = BlogPost::factory()->create([
                'student_id' => $author->id,
                'status' => 'published',
            ]);
            
            $draftPost = BlogPost::factory()->create([
                'student_id' => $author->id,
                'status' => 'draft',
            ]);
            
            $policy = new BlogPostPolicy();
            
            // Author can update both published and draft posts
            expect($policy->update($author, $publishedPost))->toBeTrue();
            expect($policy->update($author, $draftPost))->toBeTrue();
            
            // Other student cannot update either
            expect($policy->update($otherStudent, $publishedPost))->toBeFalse();
            expect($policy->update($otherStudent, $draftPost))->toBeFalse();
        });
        
    });
    
    describe('delete method', function () {
        
        it('allows only the author to delete their post', function () {
            $author = Students::factory()->create();
            $post = BlogPost::factory()->create([
                'student_id' => $author->id,
            ]);
            
            $policy = new BlogPostPolicy();
            
            // Author can delete their own post
            expect($policy->delete($author, $post))->toBeTrue();
            
            // Another student cannot delete the post
            $otherStudent = Students::factory()->create();
            expect($policy->delete($otherStudent, $post))->toBeFalse();
        });
        
        it('works for both published and draft posts', function () {
            $author = Students::factory()->create();
            $otherStudent = Students::factory()->create();
            
            $publishedPost = BlogPost::factory()->create([
                'student_id' => $author->id,
                'status' => 'published',
            ]);
            
            $draftPost = BlogPost::factory()->create([
                'student_id' => $author->id,
                'status' => 'draft',
            ]);
            
            $policy = new BlogPostPolicy();
            
            // Author can delete both published and draft posts
            expect($policy->delete($author, $publishedPost))->toBeTrue();
            expect($policy->delete($author, $draftPost))->toBeTrue();
            
            // Other student cannot delete either
            expect($policy->delete($otherStudent, $publishedPost))->toBeFalse();
            expect($policy->delete($otherStudent, $draftPost))->toBeFalse();
        });
        
    });
    
});
