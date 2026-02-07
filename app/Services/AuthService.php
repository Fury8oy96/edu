<?php

namespace App\Services;

use App\Models\Students;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Create a new AuthService instance
     * 
     * @param OtpService $otpService
     * @param TokenService $tokenService
     */
    public function __construct(
        private OtpService $otpService,
        private TokenService $tokenService
    ) {}
    
    /**
     * Register a new student and send OTP
     * 
     * @param array $data ['name', 'email', 'password', 'profession']
     * @return array ['message', 'email']
     */
    public function register(array $data): array
    {
        // Create student record with unverified status (email_verified_at is NULL by default)
        $student = Students::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Will be hashed automatically by the model's 'hashed' cast
            'profession' => $data['profession'],
        ]);
        
        // Generate and send OTP via OtpService
        $this->otpService->generateAndSend($student);
        
        // Return success message with email
        return [
            'message' => 'Registration successful. Please check your email for the OTP.',
            'email' => $student->email,
        ];
    }
    
    /**
     * Verify OTP and activate student account
     * 
     * @param string $email
     * @param string $otp
     * @return array ['message', 'token', 'student']
     * @throws \App\Exceptions\InvalidOtpException
     */
    public function verifyOtp(string $email, string $otp): array
    {
        // Find student by email
        $student = Students::where('email', $email)->first();
        
        // If student not found, throw exception
        if (!$student) {
            throw new \App\Exceptions\InvalidOtpException('Student not found');
        }
        
        // Verify OTP using OtpService
        if (!$this->otpService->verify($student, $otp)) {
            throw new \App\Exceptions\InvalidOtpException('Invalid or expired OTP');
        }
        
        // Mark email as verified (this also clears OTP data)
        $student->markEmailAsVerified();
        
        // Generate token using TokenService
        $token = $this->tokenService->generateToken($student);
        
        // Return token and student details
        return [
            'message' => 'Email verified successfully',
            'token' => $token,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'profession' => $student->profession,
                'email_verified_at' => $student->email_verified_at,
            ],
        ];
    }
    
    /**
     * Login student (generate token if verified, send OTP if not)
     * 
     * @param string $email
     * @param string $password
     * @return array ['verified' => bool, 'token'?, 'student'?, 'message'?, 'email'?]
     * @throws \App\Exceptions\InvalidCredentialsException
     */
    public function login(string $email, string $password): array
    {
        // Find student by email
        $student = Students::where('email', $email)->first();
        
        // If student not found or password invalid, throw exception
        if (!$student || !Hash::check($password, $student->password)) {
            throw new \App\Exceptions\InvalidCredentialsException('Invalid credentials');
        }
        
        // Check verification status using isVerified()
        if ($student->isVerified()) {
            // If verified: generate token and return with student details
            $token = $this->tokenService->generateToken($student);
            
            return [
                'verified' => true,
                'message' => 'Login successful',
                'token' => $token,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'profession' => $student->profession,
                    'email_verified_at' => $student->email_verified_at,
                ],
            ];
        } else {
            // If unverified: generate new OTP, send email, return verification required message
            $this->otpService->generateAndSend($student);
            
            return [
                'verified' => false,
                'message' => 'Email verification required. A new OTP has been sent to your email.',
                'email' => $student->email,
            ];
        }
    }

    /**
     * Resend OTP to unverified student
     *
     * @param string $email
     * @return array ['message', 'email']
     * @throws \App\Exceptions\AlreadyVerifiedException
     */
    public function resendOtp(string $email): array
    {
        // Find student by email
        $student = Students::where('email', $email)->first();

        // If student not found, throw exception
        if (!$student) {
            throw new \App\Exceptions\InvalidOtpException('Student not found');
        }

        // Check if already verified using isVerified()
        if ($student->isVerified()) {
            throw new \App\Exceptions\AlreadyVerifiedException('Email is already verified');
        }

        // Generate new OTP (overwrites old one) and send email via OtpService
        $this->otpService->generateAndSend($student);

        // Return success message
        return [
            'message' => 'OTP has been resent to your email.',
            'email' => $student->email,
        ];
    }

    /**
     * Logout student by revoking current token
     * 
     * @param Students $student
     * @return array ['message']
     */
    public function logout(Students $student): array
    {
        // Revoke current token using TokenService
        $this->tokenService->revokeCurrentToken($student);
        
        // Return success message
        return [
            'message' => 'Logged out successfully',
        ];
    }

}
