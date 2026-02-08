<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Students extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\StudentsFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profession',
        'avatar',
        'bio',
        'skills',
        'experience',
        'education',
        'certifications',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'otp_hash',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'skills' => 'array',
        'certifications' => 'array',
        'password' => 'hashed',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Courses::class, 'course_student', 'student_id', 'course_id')
                    ->withPivot('enrolled_at', 'status', 'progress_percentage')
                    ->withTimestamps();
    }

    /**
     * Generate and store a hashed OTP for email verification
     * 
     * @param int $length OTP length (default: 6)
     * @param int $expiryMinutes OTP expiry time in minutes (default: 10)
     * @return string The plain OTP code (to be sent via email)
     */
    public function generateOTP(int $length = 6, int $expiryMinutes = 10): string
    {
        // Generate random numeric OTP
        $otp = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        
        // Hash and store the OTP
        $this->otp_hash = Hash::make($otp);
        $this->otp_expires_at = now()->addMinutes($expiryMinutes);
        $this->save();
        
        // Return plain OTP to be sent via email
        return $otp;
    }

    /**
     * Verify the provided OTP against the stored hash
     * 
     * @param string $otp The OTP code to verify
     * @return bool True if OTP is valid and not expired
     */
    public function verifyOTP(string $otp): bool
    {
        // Check if OTP exists and hasn't expired
        if (!$this->otp_hash || !$this->otp_expires_at || $this->otp_expires_at->isPast()) {
            return false;
        }
        
        // Verify OTP hash
        return Hash::check($otp, $this->otp_hash);
    }

    /**
     * Clear OTP data after successful verification
     */
    public function clearOTP(): void
    {
        $this->otp_hash = null;
        $this->otp_expires_at = null;
        $this->save();
    }

    /**
     * Check if student's email is verified
     * 
     * @return bool
     */
    public function isVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(): void
    {
        $this->email_verified_at = now();
        $this->clearOTP();
    }

    /**
     * Get all payments for this student
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'student_id');
    }

    /**
     * Check if student has an active subscription
     * 
     * @return bool
     */
    public function hasActiveSubscription(): bool
    {
        return $this->payments()
            ->where('status', 'approved')
            ->where('subscription_expires_at', '>', now())
            ->exists();
    }

    /**
     * Get the active subscription payment record
     * 
     * @return Payment|null
     */
    public function getActiveSubscription(): ?Payment
    {
        return $this->payments()
            ->where('status', 'approved')
            ->where('subscription_expires_at', '>', now())
            ->orderBy('subscription_expires_at', 'desc')
            ->first();
    }
}
