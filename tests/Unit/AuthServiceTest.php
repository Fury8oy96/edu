<?php

use App\Mail\OtpVerificationMail;
use App\Models\Students;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Services\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->otpService = new OtpService();
    $this->tokenService = new TokenService();
    $this->authService = new AuthService($this->otpService, $this->tokenService);
});

test('register creates unverified student', function () {
    Mail::fake();
    
    $data = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'profession' => 'Software Developer',
    ];
    
    $result = $this->authService->register($data);
    
    // Assert student was created
    $student = Students::where('email', $data['email'])->first();
    expect($student)->not->toBeNull();
    expect($student->name)->toBe($data['name']);
    expect($student->email)->toBe($data['email']);
    expect($student->profession)->toBe($data['profession']);
    
    // Assert student is unverified (email_verified_at is NULL)
    expect($student->email_verified_at)->toBeNull();
    
    // Assert OTP was generated
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_expires_at)->not->toBeNull();
    
    // Assert email was sent
    Mail::assertSent(OtpVerificationMail::class, function ($mail) use ($student) {
        return $mail->hasTo($student->email) &&
               $mail->studentName === $student->name;
    });
    
    // Assert response format
    expect($result)->toHaveKey('message');
    expect($result)->toHaveKey('email');
    expect($result['email'])->toBe($data['email']);
});

test('register hashes password', function () {
    Mail::fake();
    
    $data = [
        'name' => 'John Smith',
        'email' => 'john.smith@example.com',
        'password' => 'mySecretPassword',
        'profession' => 'Designer',
    ];
    
    $this->authService->register($data);
    
    $student = Students::where('email', $data['email'])->first();
    
    // Assert password is hashed (not plain text)
    expect($student->password)->not->toBe($data['password']);
    
    // Assert password starts with bcrypt hash prefix
    expect($student->password)->toStartWith('$2y$');
    
    // Assert password can be verified
    expect(\Illuminate\Support\Facades\Hash::check($data['password'], $student->password))->toBeTrue();
});

test('register returns success message with email', function () {
    Mail::fake();
    
    $data = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'profession' => 'Tester',
    ];
    
    $result = $this->authService->register($data);
    
    expect($result)->toBeArray();
    expect($result)->toHaveKey('message');
    expect($result)->toHaveKey('email');
    expect($result['email'])->toBe($data['email']);
    expect($result['message'])->toContain('Registration successful');
});


test('verifyOtp successfully verifies valid OTP', function () {
    Mail::fake();
    
    // Create a student and generate OTP
    $student = Students::factory()->create([
        'email_verified_at' => null,
    ]);
    $otp = $student->generateOTP();
    
    // Verify OTP
    $result = $this->authService->verifyOtp($student->email, $otp);
    
    // Assert response format
    expect($result)->toBeArray();
    expect($result)->toHaveKey('message');
    expect($result)->toHaveKey('token');
    expect($result)->toHaveKey('student');
    
    // Assert message
    expect($result['message'])->toBe('Email verified successfully');
    
    // Assert token is a string
    expect($result['token'])->toBeString();
    expect($result['token'])->not->toBeEmpty();
    
    // Assert student details
    expect($result['student'])->toHaveKey('id');
    expect($result['student'])->toHaveKey('name');
    expect($result['student'])->toHaveKey('email');
    expect($result['student'])->toHaveKey('profession');
    expect($result['student'])->toHaveKey('email_verified_at');
    expect($result['student']['email'])->toBe($student->email);
    
    // Refresh student from database
    $student->refresh();
    
    // Assert email is verified
    expect($student->email_verified_at)->not->toBeNull();
    
    // Assert OTP data is cleared
    expect($student->otp_hash)->toBeNull();
    expect($student->otp_expires_at)->toBeNull();
});

test('verifyOtp throws exception for invalid OTP', function () {
    Mail::fake();
    
    // Create a student and generate OTP
    $student = Students::factory()->create([
        'email_verified_at' => null,
    ]);
    $student->generateOTP();
    
    // Try to verify with wrong OTP
    $this->authService->verifyOtp($student->email, '999999');
})->throws(\App\Exceptions\InvalidOtpException::class);

test('verifyOtp throws exception for expired OTP', function () {
    Mail::fake();
    
    // Create a student and generate OTP
    $student = Students::factory()->create([
        'email_verified_at' => null,
    ]);
    $otp = $student->generateOTP();
    
    // Manually expire the OTP
    $student->otp_expires_at = now()->subMinutes(1);
    $student->save();
    
    // Try to verify with expired OTP
    $this->authService->verifyOtp($student->email, $otp);
})->throws(\App\Exceptions\InvalidOtpException::class);

test('verifyOtp throws exception for non-existent student', function () {
    // Try to verify OTP for non-existent email
    $this->authService->verifyOtp('nonexistent@example.com', '123456');
})->throws(\App\Exceptions\InvalidOtpException::class);

test('verifyOtp clears OTP after successful verification', function () {
    Mail::fake();
    
    // Create a student and generate OTP
    $student = Students::factory()->create([
        'email_verified_at' => null,
    ]);
    $otp = $student->generateOTP();
    
    // Verify OTP should be present before verification
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_expires_at)->not->toBeNull();
    
    // Verify OTP
    $this->authService->verifyOtp($student->email, $otp);
    
    // Refresh student from database
    $student->refresh();
    
    // Assert OTP data is cleared (single-use enforcement)
    expect($student->otp_hash)->toBeNull();
    expect($student->otp_expires_at)->toBeNull();
});

test('verifyOtp generates valid Sanctum token', function () {
    Mail::fake();
    
    // Create a student and generate OTP
    $student = Students::factory()->create([
        'email_verified_at' => null,
    ]);
    $otp = $student->generateOTP();
    
    // Verify OTP
    $result = $this->authService->verifyOtp($student->email, $otp);
    
    // Assert token is returned
    expect($result['token'])->toBeString();
    expect($result['token'])->not->toBeEmpty();
    
    // Refresh student to get tokens
    $student->refresh();
    
    // Assert token was created in database
    expect($student->tokens()->count())->toBe(1);
});

test('login with verified student returns token', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'verified@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);
    
    // Login
    $result = $this->authService->login($student->email, 'password123');
    
    // Assert response format
    expect($result)->toBeArray();
    expect($result)->toHaveKey('verified');
    expect($result)->toHaveKey('message');
    expect($result)->toHaveKey('token');
    expect($result)->toHaveKey('student');
    
    // Assert verified status
    expect($result['verified'])->toBeTrue();
    
    // Assert message
    expect($result['message'])->toBe('Login successful');
    
    // Assert token is a string
    expect($result['token'])->toBeString();
    expect($result['token'])->not->toBeEmpty();
    
    // Assert student details
    expect($result['student'])->toHaveKey('id');
    expect($result['student'])->toHaveKey('name');
    expect($result['student'])->toHaveKey('email');
    expect($result['student'])->toHaveKey('profession');
    expect($result['student'])->toHaveKey('email_verified_at');
    expect($result['student']['email'])->toBe($student->email);
    
    // Assert no email was sent
    Mail::assertNothingSent();
});

test('login with unverified student sends new OTP', function () {
    Mail::fake();
    
    // Create an unverified student
    $student = Students::factory()->create([
        'email' => 'unverified@example.com',
        'password' => 'password123',
        'email_verified_at' => null,
    ]);
    
    // Login
    $result = $this->authService->login($student->email, 'password123');
    
    // Assert response format
    expect($result)->toBeArray();
    expect($result)->toHaveKey('verified');
    expect($result)->toHaveKey('message');
    expect($result)->toHaveKey('email');
    
    // Assert verified status
    expect($result['verified'])->toBeFalse();
    
    // Assert message
    expect($result['message'])->toContain('Email verification required');
    expect($result['message'])->toContain('new OTP');
    
    // Assert email is returned
    expect($result['email'])->toBe($student->email);
    
    // Assert no token is returned
    expect($result)->not->toHaveKey('token');
    expect($result)->not->toHaveKey('student');
    
    // Refresh student from database
    $student->refresh();
    
    // Assert OTP was generated
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_expires_at)->not->toBeNull();
    
    // Assert email was sent
    Mail::assertSent(OtpVerificationMail::class, function ($mail) use ($student) {
        return $mail->hasTo($student->email) &&
               $mail->studentName === $student->name;
    });
});

test('login throws exception for invalid email', function () {
    // Try to login with non-existent email
    $this->authService->login('nonexistent@example.com', 'password123');
})->throws(\App\Exceptions\InvalidCredentialsException::class);

test('login throws exception for invalid password', function () {
    Mail::fake();
    
    // Create a student
    $student = Students::factory()->create([
        'email' => 'test@example.com',
        'password' => 'correctpassword',
        'email_verified_at' => now(),
    ]);
    
    // Try to login with wrong password
    $this->authService->login($student->email, 'wrongpassword');
})->throws(\App\Exceptions\InvalidCredentialsException::class);

test('login verifies password correctly', function () {
    Mail::fake();
    
    // Create a verified student with specific password
    $student = Students::factory()->create([
        'email' => 'passwordtest@example.com',
        'password' => 'mySecurePassword123',
        'email_verified_at' => now(),
    ]);
    
    // Login with correct password should succeed
    $result = $this->authService->login($student->email, 'mySecurePassword123');
    expect($result['verified'])->toBeTrue();
    expect($result)->toHaveKey('token');
    
    // Login with incorrect password should throw exception
    expect(fn() => $this->authService->login($student->email, 'wrongPassword'))
        ->toThrow(\App\Exceptions\InvalidCredentialsException::class);
});

test('login generates new OTP for unverified student even if old OTP exists', function () {
    Mail::fake();
    
    // Create an unverified student with existing OTP
    $student = Students::factory()->create([
        'email' => 'unverified2@example.com',
        'password' => 'password123',
        'email_verified_at' => null,
    ]);
    
    // Generate initial OTP
    $oldOtp = $student->generateOTP();
    $oldOtpHash = $student->otp_hash;
    $oldExpiresAt = $student->otp_expires_at;
    
    // Wait a moment to ensure timestamps differ
    sleep(1);
    
    // Login (should generate new OTP)
    $result = $this->authService->login($student->email, 'password123');
    
    // Refresh student from database
    $student->refresh();
    
    // Assert new OTP was generated (hash should be different)
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_hash)->not->toBe($oldOtpHash);
    
    // Assert expiry time was updated
    expect($student->otp_expires_at)->not->toBeNull();
    expect($student->otp_expires_at->timestamp)->toBeGreaterThan($oldExpiresAt->timestamp);
    
    // Assert old OTP no longer works
    expect($student->verifyOTP($oldOtp))->toBeFalse();
});

test('login does not reveal whether email exists', function () {
    Mail::fake();
    
    // Create a student
    $student = Students::factory()->create([
        'email' => 'exists@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);
    
    // Try to login with non-existent email
    try {
        $this->authService->login('nonexistent@example.com', 'password123');
        expect(false)->toBeTrue(); // Should not reach here
    } catch (\App\Exceptions\InvalidCredentialsException $e) {
        $message1 = $e->getMessage();
    }
    
    // Try to login with existing email but wrong password
    try {
        $this->authService->login('exists@example.com', 'wrongpassword');
        expect(false)->toBeTrue(); // Should not reach here
    } catch (\App\Exceptions\InvalidCredentialsException $e) {
        $message2 = $e->getMessage();
    }
    
    // Both should return the same generic error message
    expect($message1)->toBe($message2);
    expect($message1)->toBe('Invalid credentials');
});

test('resendOtp sends new OTP to unverified student', function () {
    Mail::fake();
    
    // Create an unverified student
    $student = Students::factory()->create([
        'email' => 'unverified@example.com',
        'email_verified_at' => null,
    ]);
    
    // Resend OTP
    $result = $this->authService->resendOtp($student->email);
    
    // Assert response format
    expect($result)->toBeArray();
    expect($result)->toHaveKey('message');
    expect($result)->toHaveKey('email');
    
    // Assert message
    expect($result['message'])->toContain('OTP has been resent');
    
    // Assert email is returned
    expect($result['email'])->toBe($student->email);
    
    // Refresh student from database
    $student->refresh();
    
    // Assert OTP was generated
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_expires_at)->not->toBeNull();
    
    // Assert email was sent
    Mail::assertSent(OtpVerificationMail::class, function ($mail) use ($student) {
        return $mail->hasTo($student->email) &&
               $mail->studentName === $student->name;
    });
});

test('resendOtp throws exception for verified student', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
    ]);
    
    // Try to resend OTP
    $this->authService->resendOtp($student->email);
})->throws(\App\Exceptions\AlreadyVerifiedException::class);

test('resendOtp throws exception for non-existent student', function () {
    // Try to resend OTP for non-existent email
    $this->authService->resendOtp('nonexistent@example.com');
})->throws(\App\Exceptions\InvalidOtpException::class);

test('resendOtp overwrites old OTP', function () {
    Mail::fake();
    
    // Create an unverified student with existing OTP
    $student = Students::factory()->create([
        'email' => 'unverified2@example.com',
        'email_verified_at' => null,
    ]);
    
    // Generate initial OTP
    $oldOtp = $student->generateOTP();
    $oldOtpHash = $student->otp_hash;
    $oldExpiresAt = $student->otp_expires_at;
    
    // Wait a moment to ensure timestamps differ
    sleep(1);
    
    // Resend OTP (should generate new OTP)
    $result = $this->authService->resendOtp($student->email);
    
    // Refresh student from database
    $student->refresh();
    
    // Assert new OTP was generated (hash should be different)
    expect($student->otp_hash)->not->toBeNull();
    expect($student->otp_hash)->not->toBe($oldOtpHash);
    
    // Assert expiry time was updated
    expect($student->otp_expires_at)->not->toBeNull();
    expect($student->otp_expires_at->timestamp)->toBeGreaterThan($oldExpiresAt->timestamp);
    
    // Assert old OTP no longer works
    expect($student->verifyOTP($oldOtp))->toBeFalse();
});

test('resendOtp returns success message with email', function () {
    Mail::fake();
    
    // Create an unverified student
    $student = Students::factory()->create([
        'email' => 'test@example.com',
        'email_verified_at' => null,
    ]);
    
    // Resend OTP
    $result = $this->authService->resendOtp($student->email);
    
    // Assert response format
    expect($result)->toBeArray();
    expect($result)->toHaveKey('message');
    expect($result)->toHaveKey('email');
    expect($result['email'])->toBe($student->email);
    expect($result['message'])->toContain('resent');
});

test('resendOtp does not send email to verified student', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'verified2@example.com',
        'email_verified_at' => now(),
    ]);
    
    // Try to resend OTP (should throw exception)
    try {
        $this->authService->resendOtp($student->email);
    } catch (\App\Exceptions\AlreadyVerifiedException $e) {
        // Expected exception
    }
    
    // Assert no email was sent
    Mail::assertNothingSent();
});

test('logout revokes current token', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'logout@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);
    
    // Generate a token for the student
    $token = $this->tokenService->generateToken($student);
    
    // Verify token was created
    $student->refresh();
    expect($student->tokens()->count())->toBe(1);
    
    // Set the current access token (simulate authenticated request)
    $student->withAccessToken($student->tokens()->first());
    
    // Logout
    $result = $this->authService->logout($student);
    
    // Assert response format
    expect($result)->toBeArray();
    expect($result)->toHaveKey('message');
    expect($result['message'])->toBe('Logged out successfully');
    
    // Refresh student from database
    $student->refresh();
    
    // Assert token was revoked (deleted)
    expect($student->tokens()->count())->toBe(0);
});

test('logout returns success message', function () {
    Mail::fake();
    
    // Create a verified student with a token
    $student = Students::factory()->create([
        'email' => 'logout2@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);
    
    // Generate a token
    $token = $this->tokenService->generateToken($student);
    
    // Set the current access token
    $student->withAccessToken($student->tokens()->first());
    
    // Logout
    $result = $this->authService->logout($student);
    
    // Assert response format
    expect($result)->toBeArray();
    expect($result)->toHaveKey('message');
    expect($result['message'])->toContain('Logged out successfully');
});

test('logout only revokes current token not all tokens', function () {
    Mail::fake();
    
    // Create a verified student
    $student = Students::factory()->create([
        'email' => 'multitoken@example.com',
        'password' => 'password123',
        'email_verified_at' => now(),
    ]);
    
    // Generate multiple tokens (simulating multiple devices)
    $token1 = $this->tokenService->generateToken($student);
    $token2 = $this->tokenService->generateToken($student);
    
    // Verify both tokens were created
    $student->refresh();
    expect($student->tokens()->count())->toBe(2);
    
    // Set the first token as current
    $student->withAccessToken($student->tokens()->first());
    
    // Logout (should only revoke current token)
    $result = $this->authService->logout($student);
    
    // Refresh student from database
    $student->refresh();
    
    // Assert only one token was revoked (one should remain)
    expect($student->tokens()->count())->toBe(1);
});
