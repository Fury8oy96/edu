<?php

use App\Models\Students;
use App\Models\Courses;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

// Test show() endpoint
test('authenticated student can view their profile', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->getJson('/api/v1/student/profile');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'id',
            'name',
            'email',
            'profession',
            'avatar_url',
            'email_verified',
            'email_verified_at',
            'created_at',
            'statistics' => [
                'total_enrolled_courses',
                'completed_courses',
                'certificates_earned',
            ],
        ]);
});

test('unauthenticated user cannot view profile', function () {
    $response = $this->getJson('/api/v1/student/profile');
    
    $response->assertStatus(401);
});

// Test update() endpoint
test('verified student can update their name', function () {
    $student = Students::factory()->create([
        'name' => 'Old Name',
        'email_verified_at' => now(),
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->putJson('/api/v1/student/profile', [
            'name' => 'New Name',
        ]);
    
    $response->assertStatus(200)
        ->assertJson([
            'name' => 'New Name',
        ]);
    
    $this->assertDatabaseHas('students', [
        'id' => $student->id,
        'name' => 'New Name',
    ]);
});

test('verified student can update their profession', function () {
    $student = Students::factory()->create([
        'profession' => 'Old Profession',
        'email_verified_at' => now(),
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->putJson('/api/v1/student/profile', [
            'profession' => 'New Profession',
        ]);
    
    $response->assertStatus(200)
        ->assertJson([
            'profession' => 'New Profession',
        ]);
});

test('profile update validates name length', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->putJson('/api/v1/student/profile', [
            'name' => 'AB', // Too short
        ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

test('profile update rejects email changes', function () {
    $student = Students::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->putJson('/api/v1/student/profile', [
            'email' => 'new@example.com',
        ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

test('unverified student cannot update profile', function () {
    $student = Students::factory()->create([
        'email_verified_at' => null,
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->putJson('/api/v1/student/profile', [
            'name' => 'New Name',
        ]);
    
    $response->assertStatus(403);
});

// Test uploadAvatar() endpoint
test('verified student can upload avatar', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);
    
    $response = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/student/profile/avatar', [
            'avatar' => $file,
        ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure(['avatar_url']);
    
    $student->refresh();
    expect($student->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($student->avatar);
});

test('avatar upload validates file type', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $file = UploadedFile::fake()->create('document.pdf', 100);
    
    $response = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/student/profile/avatar', [
            'avatar' => $file,
        ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors('avatar');
});

test('avatar upload validates file size', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $file = UploadedFile::fake()->image('avatar.jpg')->size(3000); // 3MB
    
    $response = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/student/profile/avatar', [
            'avatar' => $file,
        ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors('avatar');
});

test('uploading new avatar replaces old one', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
        'avatar' => 'avatars/old-avatar.jpg',
    ]);
    
    // Create the old avatar file
    Storage::disk('public')->put('avatars/old-avatar.jpg', 'old content');
    
    $file = UploadedFile::fake()->image('new-avatar.jpg');
    
    $response = $this->actingAs($student, 'sanctum')
        ->postJson('/api/v1/student/profile/avatar', [
            'avatar' => $file,
        ]);
    
    $response->assertStatus(200);
    
    // Old avatar should be deleted
    Storage::disk('public')->assertMissing('avatars/old-avatar.jpg');
    
    // New avatar should exist
    $student->refresh();
    Storage::disk('public')->assertExists($student->avatar);
});

// Test removeAvatar() endpoint
test('verified student can remove avatar', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
        'avatar' => 'avatars/test-avatar.jpg',
    ]);
    
    Storage::disk('public')->put('avatars/test-avatar.jpg', 'content');
    
    $response = $this->actingAs($student, 'sanctum')
        ->deleteJson('/api/v1/student/profile/avatar');
    
    $response->assertStatus(200)
        ->assertJson([
            'avatar_url' => null,
        ]);
    
    $student->refresh();
    expect($student->avatar)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/test-avatar.jpg');
});

test('removing avatar when none exists returns 404', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
        'avatar' => null,
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->deleteJson('/api/v1/student/profile/avatar');
    
    $response->assertStatus(404);
});

// Test progress() endpoint
test('authenticated student can view learning progress', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $course = Courses::factory()->create();
    
    $student->courses()->attach($course->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 50,
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->getJson('/api/v1/student/profile/progress');
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => [
                'course_id',
                'course_name',
                'course_description',
                'enrollment' => [
                    'enrolled_at',
                    'status',
                    'progress_percentage',
                    'is_completed',
                ],
            ],
        ]);
});

test('learning progress returns empty array when no enrollments', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->getJson('/api/v1/student/profile/progress');
    
    $response->assertStatus(200)
        ->assertJson([]);
});

test('learning progress orders courses by enrollment date descending', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $course1 = Courses::factory()->create(['title' => 'Course 1']);
    $course2 = Courses::factory()->create(['title' => 'Course 2']);
    $course3 = Courses::factory()->create(['title' => 'Course 3']);
    
    // Enroll in different order
    $student->courses()->attach($course1->id, [
        'enrolled_at' => now()->subDays(3),
        'status' => 'active',
        'progress_percentage' => 30,
    ]);
    
    $student->courses()->attach($course2->id, [
        'enrolled_at' => now()->subDays(1),
        'status' => 'active',
        'progress_percentage' => 50,
    ]);
    
    $student->courses()->attach($course3->id, [
        'enrolled_at' => now()->subDays(2),
        'status' => 'active',
        'progress_percentage' => 20,
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->getJson('/api/v1/student/profile/progress');
    
    $response->assertStatus(200);
    
    $data = $response->json();
    
    // Most recent enrollment should be first
    expect($data[0]['course_name'])->toBe('Course 2');
    expect($data[1]['course_name'])->toBe('Course 3');
    expect($data[2]['course_name'])->toBe('Course 1');
});

// Test statistics() endpoint
test('authenticated student can view statistics', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $course1 = Courses::factory()->create();
    $course2 = Courses::factory()->create();
    
    $student->courses()->attach($course1->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 100,
    ]);
    
    $student->courses()->attach($course2->id, [
        'enrolled_at' => now(),
        'status' => 'active',
        'progress_percentage' => 50,
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->getJson('/api/v1/student/profile/statistics');
    
    $response->assertStatus(200)
        ->assertJson([
            'total_enrolled_courses' => 2,
            'completed_courses' => 1,
            'certificates_earned' => 1,
        ]);
});

test('statistics returns zero counts when no enrollments', function () {
    $student = Students::factory()->create([
        'email_verified_at' => now(),
    ]);
    
    $response = $this->actingAs($student, 'sanctum')
        ->getJson('/api/v1/student/profile/statistics');
    
    $response->assertStatus(200)
        ->assertJson([
            'total_enrolled_courses' => 0,
            'completed_courses' => 0,
            'certificates_earned' => 0,
        ]);
});
