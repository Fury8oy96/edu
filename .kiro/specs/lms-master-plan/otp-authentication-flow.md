# OTP Authentication Flow - Security Best Practices

## Overview

This document outlines the secure OTP (One-Time Password) authentication flow for student registration and login, following industry best practices used by major platforms.

---

## 🔒 Security Principles

### Why We Don't Store Plain OTP Codes

**Major platforms (Google, Facebook, GitHub, etc.) follow these principles:**

1. **Hashed Storage**: OTP codes are hashed (like passwords) before storage
2. **Time-Limited**: OTPs expire after a short period (typically 5-10 minutes)
3. **Single-Use**: OTPs are cleared after successful verification
4. **No Retrieval**: Once hashed, the original OTP cannot be retrieved from the database

### Our Implementation

- ✅ **Hashed OTP**: Store `otp_hash` (bcrypt) instead of plain `otp_code`
- ✅ **Expiry Time**: Store `otp_expires_at` timestamp
- ✅ **Auto-Clear**: Clear OTP data after successful verification
- ✅ **Email-Only**: OTP is sent via email, never stored in plain text

---

## 📋 Authentication Flows

### Flow 1: Student Registration

```
┌─────────────┐
│   Student   │
│  (Mobile)   │
└──────┬──────┘
       │
       │ 1. POST /api/v1/student/register
       │    { name, email, password, profession }
       ▼
┌─────────────────┐
│   Backend API   │
│                 │
│ 2. Create       │
│    student      │
│    (unverified) │
│                 │
│ 3. Generate OTP │
│    (6 digits)   │
│                 │
│ 4. Hash OTP     │
│    Store hash   │
│    + expiry     │
│                 │
│ 5. Send email   │
│    with OTP     │
└────────┬────────┘
         │
         │ 6. Response: { message: "OTP sent", email }
         ▼
┌─────────────┐
│   Student   │
│             │
│ 7. Receives │
│    OTP via  │
│    email    │
│             │
│ 8. Enters   │
│    OTP code │
└──────┬──────┘
       │
       │ 9. POST /api/v1/student/verify-otp
       │    { email, otp }
       ▼
┌─────────────────┐
│   Backend API   │
│                 │
│ 10. Find        │
│     student     │
│                 │
│ 11. Check       │
│     expiry      │
│                 │
│ 12. Verify      │
│     hash        │
│                 │
│ 13. Mark as     │
│     verified    │
│                 │
│ 14. Clear OTP   │
│     data        │
│                 │
│ 15. Generate    │
│     token       │
└────────┬────────┘
         │
         │ 16. Response: { token, student }
         ▼
┌─────────────┐
│   Student   │
│  (Verified) │
└─────────────┘
```

---

### Flow 2: Login (Unverified Student)

```
┌─────────────┐
│   Student   │
│ (Unverified)│
└──────┬──────┘
       │
       │ 1. POST /api/v1/student/login
       │    { email, password }
       ▼
┌─────────────────┐
│   Backend API   │
│                 │
│ 2. Find student │
│                 │
│ 3. Verify       │
│    password     │
│                 │
│ 4. Check if     │
│    verified     │
│    ❌ NOT       │
│    VERIFIED     │
│                 │
│ 5. Generate     │
│    new OTP      │
│                 │
│ 6. Hash & store │
│                 │
│ 7. Send email   │
│    with OTP     │
└────────┬────────┘
         │
         │ 8. Response: { 
         │      verified: false,
         │      message: "Please verify email",
         │      email
         │    }
         ▼
┌─────────────┐
│   Student   │
│             │
│ 9. Receives │
│    OTP via  │
│    email    │
│             │
│ 10. Enters  │
│     OTP     │
└──────┬──────┘
       │
       │ 11. POST /api/v1/student/verify-otp
       │     { email, otp }
       ▼
┌─────────────────┐
│   Backend API   │
│                 │
│ 12. Verify OTP  │
│                 │
│ 13. Mark as     │
│     verified    │
│                 │
│ 14. Generate    │
│     token       │
└────────┬────────┘
         │
         │ 15. Response: { token, student }
         ▼
┌─────────────┐
│   Student   │
│  (Verified) │
└─────────────┘
```

---

### Flow 3: Login (Verified Student)

```
┌─────────────┐
│   Student   │
│  (Verified) │
└──────┬──────┘
       │
       │ 1. POST /api/v1/student/login
       │    { email, password }
       ▼
┌─────────────────┐
│   Backend API   │
│                 │
│ 2. Find student │
│                 │
│ 3. Verify       │
│    password     │
│                 │
│ 4. Check if     │
│    verified     │
│    ✅ VERIFIED  │
│                 │
│ 5. Generate     │
│    token        │
│    (Sanctum)    │
└────────┬────────┘
         │
         │ 6. Response: { 
         │      verified: true,
         │      token,
         │      student
         │    }
         ▼
┌─────────────┐
│   Student   │
│ (Logged In) │
└─────────────┘
```

---

## 🔐 Security Implementation Details

### OTP Generation

```php
// In Students model
public function generateOTP(int $length = 6, int $expiryMinutes = 10): string
{
    // Generate random numeric OTP
    $otp = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    
    // Hash and store the OTP (NEVER store plain text)
    $this->otp_hash = Hash::make($otp);
    $this->otp_expires_at = now()->addMinutes($expiryMinutes);
    $this->save();
    
    // Return plain OTP to be sent via email ONLY
    return $otp;
}
```

### OTP Verification

```php
// In Students model
public function verifyOTP(string $otp): bool
{
    // Check if OTP exists and hasn't expired
    if (!$this->otp_hash || !$this->otp_expires_at || $this->otp_expires_at->isPast()) {
        return false;
    }
    
    // Verify OTP hash (like password verification)
    return Hash::check($otp, $this->otp_hash);
}
```

### OTP Cleanup

```php
// In Students model
public function clearOTP(): void
{
    $this->otp_hash = null;
    $this->otp_expires_at = null;
    $this->save();
}

public function markEmailAsVerified(): void
{
    $this->email_verified_at = now();
    $this->clearOTP(); // Clear OTP data after verification
}
```

---

## 📧 Email Template

### OTP Verification Email

```
Subject: Verify Your Email - [Platform Name]

Hi [Student Name],

Welcome to [Platform Name]!

Your verification code is:

    [OTP CODE]

This code will expire in 10 minutes.

If you didn't request this code, please ignore this email.

Best regards,
[Platform Name] Team
```

---

## 🛡️ Security Features

### 1. Hashed Storage
- ✅ OTP is hashed using bcrypt before storage
- ✅ Original OTP cannot be retrieved from database
- ✅ Even database administrators cannot see the OTP

### 2. Time-Limited
- ✅ OTP expires after 10 minutes (configurable)
- ✅ Expired OTPs are automatically rejected
- ✅ Prevents replay attacks

### 3. Single-Use
- ✅ OTP is cleared after successful verification
- ✅ Cannot be reused even within expiry time
- ✅ Prevents multiple verification attempts

### 4. Rate Limiting
- ✅ Limit OTP generation to prevent spam
- ✅ Limit verification attempts to prevent brute force
- ✅ Implement cooldown period between OTP requests

---

## 🔄 API Endpoints

### 1. Register
```
POST /api/v1/student/register

Request:
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "profession": "Software Engineer"
}

Response (201):
{
  "message": "Registration successful. Please check your email for OTP.",
  "email": "john@example.com"
}
```

### 2. Verify OTP
```
POST /api/v1/student/verify-otp

Request:
{
  "email": "john@example.com",
  "otp": "123456"
}

Response (200):
{
  "message": "Email verified successfully",
  "token": "1|abc123...",
  "student": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": "2026-02-07T18:45:00.000000Z"
  }
}

Error Response (400):
{
  "message": "Invalid or expired OTP"
}
```

### 3. Login
```
POST /api/v1/student/login

Request:
{
  "email": "john@example.com",
  "password": "SecurePass123!"
}

Response (Verified - 200):
{
  "verified": true,
  "token": "2|xyz789...",
  "student": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}

Response (Unverified - 200):
{
  "verified": false,
  "message": "Please verify your email. A new OTP has been sent.",
  "email": "john@example.com"
}

Error Response (401):
{
  "message": "Invalid credentials"
}
```

### 4. Resend OTP
```
POST /api/v1/student/resend-otp

Request:
{
  "email": "john@example.com"
}

Response (200):
{
  "message": "OTP sent successfully",
  "email": "john@example.com"
}

Error Response (429):
{
  "message": "Please wait before requesting another OTP"
}
```

---

## ⚙️ Configuration

### Environment Variables

```env
# OTP Configuration
OTP_LENGTH=6
OTP_EXPIRY_MINUTES=10
OTP_RATE_LIMIT_ATTEMPTS=3
OTP_RATE_LIMIT_DECAY_MINUTES=5

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourplatform.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 🧪 Testing Checklist

### Registration Flow
- [ ] Student can register with valid data
- [ ] OTP is sent to email
- [ ] OTP is hashed in database (not plain text)
- [ ] OTP expires after configured time
- [ ] Invalid OTP is rejected
- [ ] Expired OTP is rejected
- [ ] Successful verification marks email as verified
- [ ] OTP data is cleared after verification

### Login Flow (Unverified)
- [ ] Unverified student cannot login without OTP
- [ ] New OTP is generated and sent on login attempt
- [ ] Student can verify and complete login

### Login Flow (Verified)
- [ ] Verified student can login with email/password
- [ ] Access token is generated (Sanctum)
- [ ] No OTP required for verified students

### Security
- [ ] OTP cannot be retrieved from database
- [ ] Rate limiting prevents spam
- [ ] Brute force protection on verification
- [ ] Email validation prevents invalid addresses

---

## 📊 Database Schema

### students table (OTP-related fields)

```sql
email_verified_at  TIMESTAMP NULL
otp_hash          VARCHAR(255) NULL  -- Hashed OTP (bcrypt)
otp_expires_at    TIMESTAMP NULL     -- OTP expiry time
```

**Note**: No plain text OTP storage!

---

## ✅ Best Practices Followed

1. ✅ **Never store plain OTP** - Always hash before storage
2. ✅ **Time-limited OTPs** - Expire after 10 minutes
3. ✅ **Single-use OTPs** - Clear after verification
4. ✅ **Rate limiting** - Prevent spam and brute force
5. ✅ **Secure email delivery** - Use encrypted SMTP
6. ✅ **Clear error messages** - Don't reveal if email exists
7. ✅ **Token-based auth** - Use Sanctum for API authentication
8. ✅ **Password hashing** - Use bcrypt for passwords

---

## 🚀 Implementation Priority

This OTP authentication system will be implemented in **Feature 1: Student Authentication with OTP** as the foundation for all student-facing features.
