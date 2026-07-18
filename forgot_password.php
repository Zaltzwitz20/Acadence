<?php
/**
 * FORGOT PASSWORD - Step 1 & 2
 * User clicks "Forgot Password" and enters their registered email
 */

include 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$message = '';
$message_type = '';
$email = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Step 2: Enter registered email address
    if (empty($email)) {
        $message = 'Please enter your registered email address.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Check if email exists in database
        $sql = "SELECT user_id, full_name, email FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Step 3: Verify identity through token generation
            // Generate unique reset token
            $token = generateResetToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour
            
            // Save token to database
            $update_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('ssi', $token, $expiry, $user['user_id']);
            
            if ($update_stmt->execute()) {
                // Send reset email (for local testing, store in session)
                sendResetEmail($email, $token);
                
                // Store user info for the next step
                $_SESSION['reset_user_id'] = $user['user_id'];
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_token'] = $token;
                
                // Redirect to Step 3 & 4 (Verify & Create new password)
                header('Location: reset_password.php');
                exit();
            } else {
                $message = 'An error occurred. Please try again.';
                $message_type = 'error';
            }
            $update_stmt->close();
        } else {
            // Email doesn't exist - show error
            $message = 'No account found with this email address.';
            $message_type = 'error';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acadence - Forgot Password</title>
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
        
        .reset-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .reset-header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .reset-header .subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .reset-header .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .step-indicator .step {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #999;
        }
        
        .step-indicator .step.active {
            color: #8B5E3C;
            font-weight: 600;
        }
        
        .step-indicator .step .circle {
            display: inline-flex;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #eee;
            color: #999;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        
        .step-indicator .step.active .circle {
            background: #8B5E3C;
            color: white;
        }
        
        .step-indicator .step.completed .circle {
            background: #28a745;
            color: white;
        }
        
        .step-indicator .step-arrow {
            color: #ccc;
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            border-color: #8B5E3C;
            outline: none;
        }
        
        .reset-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #A67B5B 0%, #8B5E3C 50%, #5D4037 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .reset-btn:hover {
            transform: scale(1.02);
        }
        
        .reset-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .back-link a {
            color: #8B5E3C;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .info-text {
            color: #888;
            font-size: 13px;
            margin-top: 5px;
        }
        
        @media (max-width: 480px) {
            .reset-container {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1><i class="bi bi-book"></i> Acadence Z</h1>
            <p class="subtitle">Forgot Password</p>
            <p class="subtitle">Enter your registered email to reset your password</p>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <span class="step active">
                    <span class="circle">1</span>
                    <span>Email</span>
                </span>
                <span class="step-arrow">→</span>
                <span class="step">
                    <span class="circle">2</span>
                    <span>Verify</span>
                </span>
                <span class="step-arrow">→</span>
                <span class="step">
                    <span class="circle">3</span>
                    <span>Reset</span>
                </span>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="forgot_password.php" id="resetForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your registered email" required autofocus>
                <div class="info-text">We'll send you a link to reset your password</div>
            </div>
            
            <button type="submit" class="reset-btn" id="submitBtn">Send Reset Instructions</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Back to Login</a>
        </div>
    </div>
    
    <script>
        // Disable double submission
        document.getElementById('resetForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').textContent = 'Sending...';
        });
    </script>
</body>
</html>

