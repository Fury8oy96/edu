<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Hi {{ $studentName }},</h2>
        
        <p>Welcome to {{ config('app.name') }}!</p>
        
        <p>Your verification code is:</p>
        
        <div style="background-color: #f4f4f4; padding: 15px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;">
            {{ $otp }}
        </div>
        
        <p>This code will expire in 10 minutes.</p>
        
        <p>If you didn't request this code, please ignore this email.</p>
        
        <p>Best regards,<br>{{ config('app.name') }} Team</p>
    </div>
</body>
</html>
