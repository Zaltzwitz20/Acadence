<?php
/**
 * PASSWORD RESET SUCCESS - Step 5
 * Confirmation page after successful password reset
 */

include 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Clear any remaining reset session data
unset($_SESSION['reset_token']);
unset($_SESSION['reset_user_id']);
unset($_SESSION['reset_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acadence - Password Reset Success</title>
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #A67B5B 0%, #8B5E3C 50%, #5D4037 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .success-container {
            background: white;
            padding: 50px 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 440px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .success-container h1 {
            color: #333;
            font-size: 26px;
            margin-bottom: 10px;
        }
        
        .success-container .subtitle {
            color: #666;
            font-size: 15px;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        .success-container .message-box {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
            font-size: 14px;
        }
        
        .success-steps {
            text-align: left;
            background: #f8f9fa;
            padding: 20px 24px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }
        
        .success-steps h3 {
            font-size: 14px;
            color: #555;
            margin-bottom: 12px;
        }
        
        .success-steps ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .success-steps ul li {
            padding: 8px 0;
            color: #555;
            font-size: 14px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-steps ul li:last-child {
            border-bottom: none;
        }
        
        .success-steps ul li .bi {
            color: #8B5E3C;
            font-size: 18px;
        }
        
        .login-btn {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #A67B5B 0%, #8B5E3C 50%, #5D4037 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: transform 0.2s;
            margin-top: 10px;
        }
        
        .login-btn:hover {
            transform: scale(1.02);
        }
        
        .note {
            color: #888;
            font-size: 13px;
            margin-top: 15px;
        }
        
        @media (max-width: 480px) {
            .success-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <h1><i class="bi bi-book"></i> Acadence Z</h1>
        <p class="subtitle" style="margin-bottom: 20px;">Password Reset Successful!</p>
        
        <div class="success-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        
        <p class="subtitle">Your password has been changed successfully.</p>
        
        <div class="message-box">
            <i class="bi bi-info-circle"></i> You can now log in using your new password.
        </div>
        
        <!-- Step 5: Log in using the new password -->
        <div class="success-steps">
            <h3><i class="bi bi-clipboard"></i> What's Next?</h3>
            <ul>
                <li>
                    <i class="bi bi-arrow-right-circle"></i>
                    <span><strong>Step 5:</strong> Click the button below to log in</span>
                </li>
                <li>
                    <i class="bi bi-person-check"></i>
                    <span>Use your <strong>username</strong> and <strong>new password</strong></span>
                </li>
                <li>
                    <i class="bi bi-shield-check"></i>
                    <span>Your password has been securely updated</span>
                </li>
            </ul>
        </div>
        
        <a href="index.php" class="login-btn">
            <i class="bi bi-box-arrow-in-right"></i> Log In Now
        </a>
        
        <p class="note">
            <i class="bi bi-clock-history"></i> If you encounter any issues, please contact support.
        </p>
    </div>
</body>
</html>

