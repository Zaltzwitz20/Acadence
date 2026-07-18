<?php
/**
 * RESET PASSWORD - Step 3 & 4
 * User verifies identity and creates a new password
 */

include 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Check if we have valid reset session
$is_valid = false;
$email = '';
$token = '';

// Check for valid reset session or query parameters
if (isset($_SESSION['reset_token']) && isset($_SESSION['reset_user_id'])) {
    // Coming from forgot_password.php
    $token = $_SESSION['reset_token'];
    $user_id = $_SESSION['reset_user_id'];
    $email = $_SESSION['reset_email'];
    $is_valid = true;
} elseif (isset($_GET['token']) && isset($_GET['email'])) {
    // Coming from email link (if implemented)
    $token = $_GET['token'];
    $email = $_GET['email'];
    
    // Verify token from database
    $sql = "SELECT user_id, reset_token, reset_token_expiry FROM users WHERE email = ? AND reset_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $expiry = strtotime($user['reset_token_expiry']);
        $now = time();
        
        if ($now < $expiry) {
            // Token is still valid
            $user_id = $user['user_id'];
            $_SESSION['reset_user_id'] = $user_id;
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_token'] = $token;
            $is_valid = true;
        } else {
            // Token has expired
            $is_valid = false;
            $error = 'Your password reset link has expired. Please request a new one.';
        }
    } else {
        $is_valid = false;
        $error = 'Invalid reset link. Please request a new password reset.';
    }
    $stmt->close();
} else {
    // No reset session
    $is_valid = false;
    $error = 'Please request a password reset first.';
}

$password_error = '';
$password_success = '';

// Step 4: Process new password submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if passwords match
    if ($new_password !== $confirm_password) {
        $password_error = 'Passwords do not match.';
    } else {
        // Validate password strength
        $validation = isValidPassword($new_password);
        if (!$validation['valid']) {
            $password_error = $validation['message'];
        } else {
            // Password is valid - update it in database
            $user_id = $_SESSION['reset_user_id'];
            
            // In production, use password_hash()
            // For now, we store as plain text (but you should use password_hash)
            $hashed_password = $new_password; // Replace with password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL, password_changed_at = NOW() WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('si', $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                // Password updated successfully
                // Clear reset session
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_email']);
                
                // Redirect to success page
                header('Location: password_reset_success.php');
                exit();
            } else {
                $password_error = 'An error occurred. Please try again.';
            }
            $update_stmt->close();
        }
    }
}

// If invalid, redirect back to forgot password
if (!$is_valid && !isset($error)) {
    header('Location: forgot_password.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acadence - Reset Password</title>
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
        
        .step-indicator .step.completed {
            color: #28a745;
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
        
        .form-group .password-wrapper {
            position: relative;
        }
        
        .form-group .password-wrapper input {
            width: 100%;
            padding: 12px 45px 12px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group .password-wrapper input:focus {
            border-color: #8B5E3C;
            outline: none;
        }
        
        .form-group .password-wrapper input.error {
            border-color: #dc3545;
        }
        
        .form-group .password-wrapper input.success {
            border-color: #28a745;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            padding: 4px;
            border-radius: 4px;
            font-size: 20px;
            line-height: 1;
            background: transparent;
            user-select: none;
        }
        
        .toggle-password:hover {
            color: #8B5E3C;
            background: rgba(139, 94, 60, 0.1);
        }
        
        .toggle-password.active {
            color: #8B5E3C;
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
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 13px;
            border: 1px solid #e9ecef;
        }
        
        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 5px 0 0 0;
        }
        
        .password-requirements ul li {
            padding: 2px 0;
            color: #666;
        }
        
        .password-requirements ul li .bi {
            margin-right: 8px;
        }
        
        .password-requirements ul li.valid {
            color: #28a745;
        }
        
        .password-requirements ul li.invalid {
            color: #dc3545;
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
        
        .email-display {
            background: #f8f9fa;
            padding: 10px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #8B5E3C;
            font-size: 14px;
            color: #555;
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
            <p class="subtitle">Reset Password</p>
            <p class="subtitle">Create a new password for your account</p>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <span class="step completed">
                    <span class="circle"><i class="bi bi-check"></i></span>
                    <span>Email</span>
                </span>
                <span class="step-arrow">→</span>
                <span class="step completed">
                    <span class="circle"><i class="bi bi-check"></i></span>
                    <span>Verify</span>
                </span>
                <span class="step-arrow">→</span>
                <span class="step active">
                    <span class="circle">3</span>
                    <span>Reset</span>
                </span>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($password_error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($password_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($is_valid && isset($email)): ?>
            <div class="email-display">
                <i class="bi bi-envelope"></i> Resetting password for: <strong><?php echo htmlspecialchars($email); ?></strong>
            </div>
        <?php endif; ?>
        
        <?php if ($is_valid): ?>
            <form method="POST" action="reset_password.php" id="resetForm">
                <!-- Step 4: Create new password -->
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                        <span class="toggle-password" id="toggleNewPassword" title="Show Password" role="button" tabindex="0">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        <span class="toggle-password" id="toggleConfirmPassword" title="Show Password" role="button" tabindex="0">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>
                
                <!-- Password Requirements -->
                <div class="password-requirements" id="passwordRequirements">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li id="req-length" class="invalid">
                            <i class="bi bi-x-circle"></i> At least 8 characters long
                        </li>
                        <li id="req-upper" class="invalid">
                            <i class="bi bi-x-circle"></i> At least one uppercase letter
                        </li>
                        <li id="req-lower" class="invalid">
                            <i class="bi bi-x-circle"></i> At least one lowercase letter
                        </li>
                        <li id="req-number" class="invalid">
                            <i class="bi bi-x-circle"></i> At least one number
                        </li>
                        <li id="req-special" class="invalid">
                            <i class="bi bi-x-circle"></i> At least one special character
                        </li>
                    </ul>
                </div>
                
                <button type="submit" class="reset-btn" id="submitBtn">Reset Password</button>
            </form>
        <?php else: ?>
            <div class="message error">
                Invalid or expired reset request. Please <a href="forgot_password.php" style="color: #8B5E3C;">request a new password reset</a>.
            </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="index.php">← Back to Login</a>
        </div>
    </div>
    
    <script>
        (function() {
            'use strict';
            
            // Toggle password visibility for both fields
            function setupToggle(inputId, toggleId) {
                const input = document.getElementById(inputId);
                const toggle = document.getElementById(toggleId);
                const icon = toggle.querySelector('i');
                
                let isVisible = false;
                
                toggle.addEventListener('click', function() {
                    isVisible = !isVisible;
                    input.type = isVisible ? 'text' : 'password';
                    icon.className = isVisible ? 'bi bi-eye-slash' : 'bi bi-eye';
                    toggle.classList.toggle('active');
                });
            }
            
            setupToggle('new_password', 'toggleNewPassword');
            setupToggle('confirm_password', 'toggleConfirmPassword');
            
            // Password validation on input
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            // Requirements elements
            const reqLength = document.getElementById('req-length');
            const reqUpper = document.getElementById('req-upper');
            const reqLower = document.getElementById('req-lower');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');
            
            function validatePassword(password) {
                const checks = {
                    length: password.length >= 8,
                    upper: /[A-Z]/.test(password),
                    lower: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };
                
                // Update requirement display
                updateRequirement(reqLength, checks.length);
                updateRequirement(reqUpper, checks.upper);
                updateRequirement(reqLower, checks.lower);
                updateRequirement(reqNumber, checks.number);
                updateRequirement(reqSpecial, checks.special);
                
                return Object.values(checks).every(Boolean);
            }
            
            function updateRequirement(element, isValid) {
                const icon = element.querySelector('.bi');
                if (isValid) {
                    element.className = 'valid';
                    icon.className = 'bi bi-check-circle';
                } else {
                    element.className = 'invalid';
                    icon.className = 'bi bi-x-circle';
                }
            }
            
            newPassword.addEventListener('input', function() {
                validatePassword(this.value);
                
                // Also check if passwords match
                if (confirmPassword.value && this.value !== confirmPassword.value) {
                    confirmPassword.style.borderColor = '#dc3545';
                } else if (confirmPassword.value) {
                    confirmPassword.style.borderColor = '#28a745';
                }
            });
            
            confirmPassword.addEventListener('input', function() {
                if (newPassword.value && this.value !== newPassword.value) {
                    this.style.borderColor = '#dc3545';
                } else if (newPassword.value && this.value === newPassword.value) {
                    this.style.borderColor = '#28a745';
                }
            });
            
            // Form validation before submit
            document.getElementById('resetForm').addEventListener('submit', function(e) {
                const password = newPassword.value;
                const confirm = confirmPassword.value;
                
                if (!validatePassword(password)) {
                    e.preventDefault();
                    alert('Please meet all password requirements.');
                    return false;
                }
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    confirmPassword.focus();
                    return false;
                }
                
                document.getElementById('submitBtn').disabled = true;
                document.getElementById('submitBtn').textContent = 'Updating Password...';
            });
            
        })();
    </script>
</body>
</html>

