<?php

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Models\Students;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /**
     * Generate OTP and send email to student
     * 
     * @param Students $student
     * @return void
     */
    public function generateAndSend(Students $student): void
    {
        // Generate OTP using the Students model method
        // This will create a 6-digit OTP, hash it, store the hash, and set expiry
        $otp = $student->generateOTP();
        
        // Send OTP via email
        Mail::to($student->email)->send(
            new OtpVerificationMail($student->name, $otp)
        );
    }
    
    /**
     * Verify OTP for a student
     * 
     * @param Students $student
     * @param string $otp
     * @return bool True if OTP is valid and not expired
     */
    public function verify(Students $student, string $otp): bool
    {
        // Use the Students model method to verify OTP
        return $student->verifyOTP($otp);
    }
}
