<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            font-size: 16px;
            line-height: 1.5;
        }
        .token {
            font-weight: bold;
            color: #333;
            background-color: #e6e6e6;
            padding: 10px;
            border-radius: 4px;
            display: inline-block;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Password Reset Request</h1>
        </header>
        <main class="content">
            <p>Hello {{ $user->name }},</p>
            <p>We received a request to reset your password. Use the following token to reset your password:</p>
            <p class="token">{{ $token }}</p>
            <p>If you did not request a password reset, please ignore this email.</p>
        </main>
        <footer class="footer">
            <p>&copy; {{ date('Y') }}.</p>
        </footer>
    </div>
</body>
</html>
