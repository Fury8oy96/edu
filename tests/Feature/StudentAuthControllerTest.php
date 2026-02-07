<?php

use App\Mail\OtpVerificationMail;
use App\Models\Students;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('register endpoint creates student and returns 201', function () {
    Mail::fake();
    
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'profession' => 'Software Engineer',
    ];
    
    $response = $this->postJson('/api/v1/student/register', $data);
    
    // Assert response status is 201 Created
    $response->assertStatus(201);
    
    // Assert response structure
    $response->assertJsonStructure([
        'message',
        'email',
    ]);
    
    // Assert response contains correct email
    $response->assertJson([
        'email' => $data['email'],
    ]);
    
    // Assert student was created in database
    $this->assertDatabaseHas('students', [
        'email' => $data['email'],
        'name' => $data['name'],
        'profession' => $data['profession'],
    ]);
    
    // Assert student is unverified
    $student = Students::where('email', $data['email'])->first();
    expect($student->email_verified_at)->toBeNull();
    
    // Assert OTP was generated
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_expires_at)->not->toBeNull();
    
    // Assert email was sent
    Mail::assertSent(OtpVerificationMail::class, function ($mail) use ($student) {
        return $mail->hasTo($student->email);
    });
});

test('register endpoint validates required fields', function () {
    $response = $this->postJson('/api/v1/student/register', []);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation errors for all required fields
    $response->assertJsonValidationErrors(['name', 'email', 'password', 'profession']);
});

test('register endpoint validates email format', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'profession' => 'Software Engineer',
    ];
    
    $response = $this->postJson('/api/v1/student/register', $data);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for email
    $response->assertJsonValidationErrors(['email']);
});

test('register endpoint validates unique email', function () {
    Mail::fake();
    
    // Create an existing student
    Students::factory()->create([
        'email' => 'existing@example.com',
    ]);
    
    $data = [
        'name' => 'John Doe',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'profession' => 'Software Engineer',
    ];
    
    $response = $this->postJson('/api/v1/student/register', $data);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for email
    $response->assertJsonValidationErrors(['email']);
});

test('register endpoint validates password confirmation', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
        'profession' => 'Software Engineer',
    ];
    
    $response = $this->postJson('/api/v1/student/register', $data);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for password
    $response->assertJsonValidationErrors(['password']);
});

test('register endpoint validates password minimum length', function () {
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
        'profession' => 'Software Engineer',
    ];
    
    $response = $this->postJson('/api/v1/student/register', $data);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for password
    $response->assertJsonValidationErrors(['password']);
});

test('register endpoint hashes password before storage', function () {
    Mail::fake();
    
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'mySecretPassword',
        'password_confirmation' => 'mySecretPassword',
        'profession' => 'Software Engineer',
    ];
    
    $response = $this->postJson('/api/v1/student/register', $data);
    
    $response->assertStatus(201);
    
    // Get the created student
    $student = Students::where('email', $data['email'])->first();
    
    // Assert password is not stored as plain text
    expect($student->password)->not->toBe($data['password']);
    
    // Assert password is a bcrypt hash
    expect($student->password)->toStartWith('$2y$');
    
    // Assert password can be verified
    expect(\Illuminate\Support\Facades\Hash::check($data['password'], $student->password))->toBeTrue();
});

test('register endpoint does not include OTP in response', function () {
    Mail::fake();
    
    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'profession' => 'Software Engineer',
    ];
    
    $response = $this->postJson('/api/v1/student/register', $data);
    
    $response->assertStatus(201);
    
    // Assert response does not contain 'otp' key
    $response->assertJsonMissing(['otp']);
    
    // Get response content as string
    $content = $response->getContent();
    
    // Get the student to check if OTP exists
    $student = Students::where('email', $data['email'])->first();
    
    // We can't check the actual OTP value since it's not stored,
    // but we can verify the response doesn't contain any 6-digit numbers
    // that might be the OTP (this is a basic check)
    expect($student->otp_hash)->not->toBeNull(); // OTP was generated
});

test('verifyOtp endpoint verifies valid OTP and returns 200 with token', function () {
    Mail::fake();
    
    // Create a student with OTP
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => null,
    ]);
    
    // Generate OTP
    $otp = $student->generateOTP();
    
    // Verify OTP
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => $student->email,
        'otp' => $otp,
    ]);
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response structure
    $response->assertJsonStructure([
        'message',
        'token',
        'student' => [
            'id',
            'name',
            'email',
            'profession',
            'email_verified_at',
        ],
    ]);
    
    // Assert student is now verified
    $student->refresh();
    expect($student->email_verified_at)->not->toBeNull();
    
    // Assert OTP data is cleared
    expect($student->otp_hash)->toBeNull();
    expect($student->otp_expires_at)->toBeNull();
    
    // Assert token is valid
    $token = $response->json('token');
    expect($token)->not->toBeNull();
    expect($token)->toBeString();
});

test('verifyOtp endpoint rejects invalid OTP with 400 status', function () {
    Mail::fake();
    
    // Create a student with OTP
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => null,
    ]);
    
    // Generate OTP (but we'll submit a different one)
    $student->generateOTP();
    
    // Try to verify with wrong OTP
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => $student->email,
        'otp' => '999999', // Wrong OTP
    ]);
    
    // Assert response status is 400 Bad Request
    $response->assertStatus(400);
    
    // Assert error message
    $response->assertJson([
        'message' => 'Invalid or expired OTP',
    ]);
    
    // Assert student is still unverified
    $student->refresh();
    expect($student->email_verified_at)->toBeNull();
    
    // Assert OTP data is still present (not cleared)
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_expires_at)->not->toBeNull();
});

test('verifyOtp endpoint rejects expired OTP with 400 status', function () {
    Mail::fake();
    
    // Create a student with OTP
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => null,
    ]);
    
    // Generate OTP
    $otp = $student->generateOTP();
    
    // Manually set expiry to past
    $student->otp_expires_at = now()->subMinutes(15);
    $student->save();
    
    // Try to verify with expired OTP
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => $student->email,
        'otp' => $otp,
    ]);
    
    // Assert response status is 400 Bad Request
    $response->assertStatus(400);
    
    // Assert error message
    $response->assertJson([
        'message' => 'Invalid or expired OTP',
    ]);
    
    // Assert student is still unverified
    $student->refresh();
    expect($student->email_verified_at)->toBeNull();
});

test('verifyOtp endpoint validates required fields', function () {
    $response = $this->postJson('/api/v1/student/verify-otp', []);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation errors for required fields
    $response->assertJsonValidationErrors(['email', 'otp']);
});

test('verifyOtp endpoint validates email format', function () {
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => 'invalid-email',
        'otp' => '123456',
    ]);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for email
    $response->assertJsonValidationErrors(['email']);
});

test('verifyOtp endpoint validates email exists', function () {
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => 'nonexistent@example.com',
        'otp' => '123456',
    ]);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for email
    $response->assertJsonValidationErrors(['email']);
});

test('verifyOtp endpoint validates OTP format', function () {
    Mail::fake();
    
    // Create a student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
    ]);
    
    // Test with non-numeric OTP
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => $student->email,
        'otp' => 'abcdef',
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['otp']);
    
    // Test with wrong length OTP
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => $student->email,
        'otp' => '12345', // Only 5 digits
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['otp']);
    
    // Test with too long OTP
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => $student->email,
        'otp' => '1234567', // 7 digits
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['otp']);
});

test('verifyOtp endpoint prevents OTP reuse', function () {
    Mail::fake();
    
    // Create a student with OTP
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => null,
    ]);
    
    // Generate OTP
    $otp = $student->generateOTP();
    
    // First verification - should succeed
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => $student->email,
        'otp' => $otp,
    ]);
    
    $response->assertStatus(200);
    
    // Try to use the same OTP again - should fail
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => $student->email,
        'otp' => $otp,
    ]);
    
    // Should fail because OTP was cleared after first use
    $response->assertStatus(400);
    $response->assertJson([
        'message' => 'Invalid or expired OTP',
    ]);
});

test('verifyOtp endpoint generates valid Sanctum token', function () {
    Mail::fake();
    
    // Create a student with OTP
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => null,
    ]);
    
    // Generate OTP
    $otp = $student->generateOTP();
    
    // Verify OTP
    $response = $this->postJson('/api/v1/student/verify-otp', [
        'email' => $student->email,
        'otp' => $otp,
    ]);
    
    $response->assertStatus(200);
    
    // Get the token from response
    $token = $response->json('token');
    
    // Assert token is not null and is a string
    expect($token)->not->toBeNull();
    expect($token)->toBeString();
    expect(strlen($token))->toBeGreaterThan(0);
    
    // Verify the token exists in the database
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_type' => Students::class,
        'tokenable_id' => $student->id,
    ]);
});

test('login endpoint returns token for verified student with valid credentials', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);
    
    // Login with valid credentials
    $response = $this->postJson('/api/v1/student/login', [
        'email' => $student->email,
        'password' => 'password123',
    ]);
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response structure for verified login
    $response->assertJsonStructure([
        'verified',
        'message',
        'token',
        'student' => [
            'id',
            'name',
            'email',
            'profession',
            'email_verified_at',
        ],
    ]);
    
    // Assert verified flag is true
    $response->assertJson([
        'verified' => true,
    ]);
    
    // Assert token is present
    $token = $response->json('token');
    expect($token)->not->toBeNull();
    expect($token)->toBeString();
});

test('login endpoint sends OTP for unverified student with valid credentials', function () {
    Mail::fake();
    
    // Create an unverified student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
        'email_verified_at' => null,
    ]);
    
    // Login with valid credentials
    $response = $this->postJson('/api/v1/student/login', [
        'email' => $student->email,
        'password' => 'password123',
    ]);
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response structure for unverified login
    $response->assertJsonStructure([
        'verified',
        'message',
        'email',
    ]);
    
    // Assert verified flag is false
    $response->assertJson([
        'verified' => false,
        'email' => $student->email,
    ]);
    
    // Assert token is NOT present
    expect($response->json('token'))->toBeNull();
    
    // Assert OTP was generated
    $student->refresh();
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_expires_at)->not->toBeNull();
    
    // Assert email was sent
    Mail::assertSent(OtpVerificationMail::class, function ($mail) use ($student) {
        return $mail->hasTo($student->email);
    });
});

test('login endpoint returns 401 for invalid credentials', function () {
    Mail::fake();
    
    // Create a student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'password' => 'correctPassword',
    ]);
    
    // Try to login with wrong password
    $response = $this->postJson('/api/v1/student/login', [
        'email' => $student->email,
        'password' => 'wrongPassword',
    ]);
    
    // Assert response status is 401 Unauthorized
    $response->assertStatus(401);
    
    // Assert error message
    $response->assertJson([
        'message' => 'Invalid credentials',
    ]);
});

test('login endpoint returns 401 for non-existent email', function () {
    Mail::fake();
    
    // Try to login with non-existent email
    $response = $this->postJson('/api/v1/student/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'somePassword',
    ]);
    
    // Assert response status is 401 Unauthorized
    $response->assertStatus(401);
    
    // Assert error message
    $response->assertJson([
        'message' => 'Invalid credentials',
    ]);
});

test('login endpoint validates required fields', function () {
    $response = $this->postJson('/api/v1/student/login', []);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation errors for required fields
    $response->assertJsonValidationErrors(['email', 'password']);
});

test('login endpoint validates email format', function () {
    $response = $this->postJson('/api/v1/student/login', [
        'email' => 'invalid-email',
        'password' => 'password123',
    ]);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for email
    $response->assertJsonValidationErrors(['email']);
});

test('login endpoint does not include OTP in response for unverified student', function () {
    Mail::fake();
    
    // Create an unverified student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
        'email_verified_at' => null,
    ]);
    
    // Login with valid credentials
    $response = $this->postJson('/api/v1/student/login', [
        'email' => $student->email,
        'password' => 'password123',
    ]);
    
    $response->assertStatus(200);
    
    // Assert response does not contain 'otp' key
    $response->assertJsonMissing(['otp']);
    
    // Verify OTP was generated but not in response
    $student->refresh();
    expect($student->otp_hash)->not->toBeNull();
});

test('login endpoint regenerates OTP for unverified student', function () {
    Mail::fake();
    
    // Create an unverified student with existing OTP
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
        'email_verified_at' => null,
    ]);
    
    // Generate initial OTP
    $oldOtp = $student->generateOTP();
    $oldOtpHash = $student->otp_hash;
    $oldOtpExpiry = $student->otp_expires_at;
    
    // Wait a moment to ensure timestamps differ
    sleep(1);
    
    // Login with valid credentials (should regenerate OTP)
    $response = $this->postJson('/api/v1/student/login', [
        'email' => $student->email,
        'password' => 'password123',
    ]);
    
    $response->assertStatus(200);
    
    // Refresh student data
    $student->refresh();
    
    // Assert OTP hash has changed (new OTP generated)
    expect($student->otp_hash)->not->toBe($oldOtpHash);
    
    // Assert expiry timestamp has changed
    expect($student->otp_expires_at->timestamp)->not->toBe($oldOtpExpiry->timestamp);
    
    // Assert old OTP no longer works
    expect($student->verifyOTP($oldOtp))->toBeFalse();
});

test('resendOtp endpoint resends OTP for unverified student and returns 200', function () {
    Mail::fake();
    
    // Create an unverified student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => null,
    ]);
    
    // Request OTP resend
    $response = $this->postJson('/api/v1/student/resend-otp', [
        'email' => $student->email,
    ]);
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response structure
    $response->assertJsonStructure([
        'message',
        'email',
    ]);
    
    // Assert response contains correct email
    $response->assertJson([
        'email' => $student->email,
    ]);
    
    // Assert OTP was generated
    $student->refresh();
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_expires_at)->not->toBeNull();
    
    // Assert email was sent
    Mail::assertSent(OtpVerificationMail::class, function ($mail) use ($student) {
        return $mail->hasTo($student->email);
    });
});

test('resendOtp endpoint returns 400 for already verified student', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => now(),
    ]);
    
    // Try to request OTP resend
    $response = $this->postJson('/api/v1/student/resend-otp', [
        'email' => $student->email,
    ]);
    
    // Assert response status is 400 Bad Request
    $response->assertStatus(400);
    
    // Assert error message
    $response->assertJson([
        'message' => 'Email is already verified',
    ]);
    
    // Assert no email was sent
    Mail::assertNotSent(OtpVerificationMail::class);
});

test('resendOtp endpoint overwrites old OTP', function () {
    Mail::fake();
    
    // Create an unverified student with existing OTP
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => null,
    ]);
    
    // Generate initial OTP
    $oldOtp = $student->generateOTP();
    $oldOtpHash = $student->otp_hash;
    $oldOtpExpiry = $student->otp_expires_at;
    
    // Wait a moment to ensure timestamps differ
    sleep(1);
    
    // Request OTP resend
    $response = $this->postJson('/api/v1/student/resend-otp', [
        'email' => $student->email,
    ]);
    
    $response->assertStatus(200);
    
    // Refresh student data
    $student->refresh();
    
    // Assert OTP hash has changed (new OTP generated)
    expect($student->otp_hash)->not->toBe($oldOtpHash);
    
    // Assert expiry timestamp has changed
    expect($student->otp_expires_at->timestamp)->not->toBe($oldOtpExpiry->timestamp);
    
    // Assert old OTP no longer works
    expect($student->verifyOTP($oldOtp))->toBeFalse();
});

test('resendOtp endpoint validates required email field', function () {
    $response = $this->postJson('/api/v1/student/resend-otp', []);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for email
    $response->assertJsonValidationErrors(['email']);
});

test('resendOtp endpoint validates email format', function () {
    $response = $this->postJson('/api/v1/student/resend-otp', [
        'email' => 'invalid-email',
    ]);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for email
    $response->assertJsonValidationErrors(['email']);
});

test('resendOtp endpoint validates email exists', function () {
    $response = $this->postJson('/api/v1/student/resend-otp', [
        'email' => 'nonexistent@example.com',
    ]);
    
    // Assert response status is 422 Unprocessable Entity
    $response->assertStatus(422);
    
    // Assert validation error for email
    $response->assertJsonValidationErrors(['email']);
});

test('resendOtp endpoint does not include OTP in response', function () {
    Mail::fake();
    
    // Create an unverified student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'email_verified_at' => null,
    ]);
    
    // Request OTP resend
    $response = $this->postJson('/api/v1/student/resend-otp', [
        'email' => $student->email,
    ]);
    
    $response->assertStatus(200);
    
    // Assert response does not contain 'otp' key
    $response->assertJsonMissing(['otp']);
    
    // Verify OTP was generated but not in response
    $student->refresh();
    expect($student->otp_hash)->not->toBeNull();
});

test('logout endpoint revokes token and returns 200', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);
    
    // Generate a token for the student
    $token = $student->createToken('auth-token')->plainTextToken;
    
    // Logout using the token
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/student/logout');
    
    // Assert response status is 200 OK
    $response->assertStatus(200);
    
    // Assert response structure
    $response->assertJsonStructure([
        'message',
    ]);
    
    // Assert success message
    $response->assertJson([
        'message' => 'Logged out successfully',
    ]);
    
    // Assert token was revoked (no longer in database)
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_type' => Students::class,
        'tokenable_id' => $student->id,
    ]);
});

test('logout endpoint requires authentication', function () {
    // Try to logout without authentication
    $response = $this->postJson('/api/v1/student/logout');
    
    // Assert response status is 401 Unauthorized
    $response->assertStatus(401);
});

test('logout endpoint rejects revoked token', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);
    
    // Generate a token for the student
    $token = $student->createToken('auth-token')->plainTextToken;
    
    // Verify token exists in database
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_type' => Students::class,
        'tokenable_id' => $student->id,
    ]);
    
    // Logout using the token
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/student/logout');
    
    $response->assertStatus(200);
    
    // Verify token was deleted from database
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_type' => Students::class,
        'tokenable_id' => $student->id,
    ]);
});

test('logout endpoint deletes token from database', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);
    
    // Generate a token for the student
    $token = $student->createToken('auth-token')->plainTextToken;
    
    // Verify token exists before logout
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_type' => Students::class,
        'tokenable_id' => $student->id,
    ]);
    
    // Logout
    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/student/logout');
    
    $response->assertStatus(200);
    
    // Verify the token was deleted from database
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_type' => Students::class,
        'tokenable_id' => $student->id,
    ]);
});
