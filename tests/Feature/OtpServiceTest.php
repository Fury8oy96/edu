<?php

use App\Mail\OtpVerificationMail;
use App\Models\Students;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->otpService = new OtpService();
});

test('generateAndSend creates OTP and sends email', function () {
    Mail::fake();
    
    $student = Students::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    
    $this->otpService->generateAndSend($student);
    
    // Refresh student to get updated data
    $student->refresh();
    
    // Assert OTP hash was created
    expect($student->otp_hash)->not->toBeNull();
    
    // Assert OTP expiry was set
    expect($student->otp_expires_at)->not->toBeNull();
    
    // Assert email was sent
    Mail::assertSent(OtpVerificationMail::class, function ($mail) use ($student) {
        return $mail->hasTo($student->email) &&
               $mail->studentName === $student->name;
    });
});

test('verify returns true for valid OTP', function () {
    $student = Students::factory()->create();
    
    // Generate OTP
    $otp = $student->generateOTP();
    
    // Verify OTP
    $result = $this->otpService->verify($student, $otp);
    
    expect($result)->toBeTrue();
});

test('verify returns false for invalid OTP', function () {
    $student = Students::factory()->create();
    
    // Generate OTP
    $student->generateOTP();
    
    // Try to verify with wrong OTP
    $result = $this->otpService->verify($student, '000000');
    
    expect($result)->toBeFalse();
});

test('verify returns false for expired OTP', function () {
    $student = Students::factory()->create();
    
    // Generate OTP
    $otp = $student->generateOTP();
    
    // Manually set expiry to past
    $student->otp_expires_at = now()->subMinutes(1);
    $student->save();
    
    // Try to verify expired OTP
    $result = $this->otpService->verify($student, $otp);
    
    expect($result)->toBeFalse();
});

test('generateAndSend creates 6-digit numeric OTP', function () {
    Mail::fake();
    
    $student = Students::factory()->create();
    
    $this->otpService->generateAndSend($student);
    
    // We can't directly access the OTP, but we can verify the hash exists
    $student->refresh();
    expect($student->otp_hash)->not->toBeNull();
    
    // Verify that a 6-digit OTP was sent in the email
    Mail::assertSent(OtpVerificationMail::class, function ($mail) {
        return preg_match('/^\d{6}$/', $mail->otp) === 1;
    });
});

test('generateAndSend sets expiry to 10 minutes', function () {
    Mail::fake();
    
    $student = Students::factory()->create();
    
    $beforeGeneration = now();
    $this->otpService->generateAndSend($student);
    $afterGeneration = now();
    
    $student->refresh();
    
    // Check expiry is approximately 10 minutes from now (within 5 seconds tolerance)
    $expectedExpiry = $beforeGeneration->addMinutes(10);
    $actualExpiry = $student->otp_expires_at;
    
    expect($actualExpiry->diffInSeconds($expectedExpiry, false))->toBeLessThanOrEqual(5);
});
