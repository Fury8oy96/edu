<?php

use App\Models\Students;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tokenService = new TokenService();
});

test('generateToken creates a valid token for student', function () {
    // Create a student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Generate token
    $token = $this->tokenService->generateToken($student);
    
    // Assert token is a non-empty string
    expect($token)->toBeString()->not->toBeEmpty();
    
    // Assert token has the expected format (should contain a pipe separator)
    expect($token)->toContain('|');
    
    // Assert the student now has a token in the database
    expect($student->tokens()->count())->toBe(1);
    
    // Assert the token name is 'api-token'
    expect($student->tokens()->first()->name)->toBe('api-token');
});

test('generateToken creates multiple tokens for same student', function () {
    // Create a student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Generate first token
    $token1 = $this->tokenService->generateToken($student);
    
    // Generate second token
    $token2 = $this->tokenService->generateToken($student);
    
    // Assert both tokens are different
    expect($token1)->not->toBe($token2);
    
    // Assert the student now has 2 tokens
    expect($student->tokens()->count())->toBe(2);
});

test('revokeCurrentToken deletes the current token', function () {
    // Create a student
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    // Generate a token
    $token = $this->tokenService->generateToken($student);
    
    // Assert token exists
    expect($student->tokens()->count())->toBe(1);
    
    // Simulate the student using the token by setting currentAccessToken
    // We need to authenticate as this student with the token
    $tokenParts = explode('|', $token);
    $tokenId = $tokenParts[0];
    
    // Get the token model
    $tokenModel = $student->tokens()->where('id', $tokenId)->first();
    
    // Set it as the current access token
    $student->withAccessToken($tokenModel);
    
    // Revoke the current token
    $this->tokenService->revokeCurrentToken($student);
    
    // Assert token is deleted
    expect($student->tokens()->count())->toBe(0);
});
