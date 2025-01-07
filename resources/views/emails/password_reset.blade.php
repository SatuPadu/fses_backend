<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body>
    <p>Hello,</p>
    <p>We received a request to reset your password. You can reset your password by clicking the link below:</p>
    <p>
        <a href="{{ $resetUrl }}" style="color: blue; text-decoration: underline;">Reset Password</a>
    </p>
    <p>If you did not request a password reset, no action is required.</p>
    <p>Thank you!</p>
</body>
</html>