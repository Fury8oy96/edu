<?php

use App\Http\Resources\StudentProfileResource;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('StudentProfileResource transforms student model correctly', function () {
    // Create a verified student
    $student = Students::create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => bcrypt('password'),
        'profession' => 'Software Engineer',
        'email_verified_at' => now()->subDays(5),
    ]);
    
    // Transform using resource
    $resource = new StudentProfileResource($student);
    $array = $resource->toArray(request());
    
    // Assert all required fields are present
    expect($array)->toHaveKeys([
        'id', 'name', 'email', 'profession', 'avatar_url',
        'email_verified', 'email_verified_at', 'created_at'
    ]);
    
    // Assert values are correct
    expect($array['id'])->toBe($student->id);
    expect($array['name'])->toBe('John Doe');
    expect($array['email'])->toBe('john@example.com');
    expect($array['profession'])->toBe('Software Engineer');
    expect($array['avatar_url'])->toBeNull();
    expect($array['email_verified'])->toBeTrue();
    
    // Assert timestamps are in ISO 8601 format
    expect($array['email_verified_at'])->toBeString();
    expect($array['created_at'])->toBeString();
    
    // Verify ISO 8601 format (contains 'T' and 'Z')
    expect($array['email_verified_at'])->toContain('T');
    expect($array['created_at'])->toContain('T');
});

test('StudentProfileResource handles null profession', function () {
    // Create a student without profession
    $student = Students::create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'password' => bcrypt('password'),
        'profession' => null,
        'email_verified_at' => now(),
    ]);
    
    // Transform using resource
    $resource = new StudentProfileResource($student);
    $array = $resource->toArray(request());
    
    // Assert profession is null
    expect($array['profession'])->toBeNull();
});

test('StudentProfileResource handles unverified email', function () {
    // Create an unverified student
    $student = Students::create([
        'name' => 'Bob Wilson',
        'email' => 'bob@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => null,
    ]);
    
    // Transform using resource
    $resource = new StudentProfileResource($student);
    $array = $resource->toArray(request());
    
    // Assert email_verified is false
    expect($array['email_verified'])->toBeFalse();
    expect($array['email_verified_at'])->toBeNull();
});

test('StudentProfileResource generates avatar URL when avatar exists', function () {
    // Mock Storage facade
    Storage::fake('public');
    
    // Create a student with avatar
    $student = Students::create([
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
        'password' => bcrypt('password'),
        'avatar' => 'avatars/test-avatar.jpg',
        'email_verified_at' => now(),
    ]);
    
    // Transform using resource
    $resource = new StudentProfileResource($student);
    $array = $resource->toArray(request());
    
    // Assert avatar_url is generated
    expect($array['avatar_url'])->toBeString();
    expect($array['avatar_url'])->toContain('avatars/test-avatar.jpg');
});

test('StudentProfileResource includes statistics when provided', function () {
    // Create a student
    $student = Students::create([
        'name' => 'Charlie Brown',
        'email' => 'charlie@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Add statistics as a dynamic property
    $student->statistics = [
        'total_enrolled_courses' => 5,
        'completed_courses' => 2,
        'certificates_earned' => 1,
    ];
    
    // Transform using resource
    $resource = new StudentProfileResource($student);
    $array = $resource->toArray(request());
    
    // Assert statistics are included
    expect($array)->toHaveKey('statistics');
    expect($array['statistics'])->toBeArray();
    expect($array['statistics']['total_enrolled_courses'])->toBe(5);
    expect($array['statistics']['completed_courses'])->toBe(2);
    expect($array['statistics']['certificates_earned'])->toBe(1);
});

test('StudentProfileResource excludes statistics when not provided', function () {
    // Create a student without statistics
    $student = Students::create([
        'name' => 'David Lee',
        'email' => 'david@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Transform using resource
    $resource = new StudentProfileResource($student);
    $array = $resource->toArray(request());
    
    // Assert statistics are not included
    expect($array)->not->toHaveKey('statistics');
});

test('StudentProfileResource formats timestamps in ISO 8601', function () {
    // Create a student with specific timestamps
    $student = Students::create([
        'name' => 'Emma Davis',
        'email' => 'emma@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now()->subDays(7),
    ]);
    
    // Transform using resource
    $resource = new StudentProfileResource($student);
    $array = $resource->toArray(request());
    
    // Assert timestamps are in ISO 8601 format
    expect($array['email_verified_at'])->toBeString();
    expect($array['created_at'])->toBeString();
    
    // Verify ISO 8601 format pattern (YYYY-MM-DDTHH:MM:SS.sss...Z with 3-6 decimal places)
    expect($array['email_verified_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3,6}Z$/');
    expect($array['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3,6}Z$/');
});
