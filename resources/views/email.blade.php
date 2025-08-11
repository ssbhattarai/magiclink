<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Link Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .content {
            text-align: center;
            margin-bottom: 30px;
        }
        .content p {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }
        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: #ffffff;
            text-decoration: none;
        }
        .expiration-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 30px;
            font-size: 14px;
            color: #856404;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
        .security-note {
            background-color: #e8f4fd;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Magic Link Login</h1>
        </div>
        
        <div class="content">
            <p>Hello! We received a request to log you in. Click the button below to securely access your account:</p>
            
            <a href="{{ $link }}" class="login-button">
                Log In Securely
            </a>
        </div>
        
        <div class="expiration-notice">
            <strong>Time Sensitive:</strong> This link will expire in {{ config('magiclink.link_expiration') }} minutes for your security.
        </div>
        
        <div class="security-note">
            <strong>Security Note:</strong> If you didn't request this login link, you can safely ignore this email. Your account remains secure.
        </div>
        
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
