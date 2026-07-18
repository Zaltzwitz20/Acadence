<?php
// Include database connection
include 'config.php';

// Check for session expiry message
if (isset($_GET['error']) && strpos($_GET['error'], 'Session expired') !== false) {
    $error = urldecode($_GET['error']);
}

if (isset($_GET['message']) && strpos($_GET['message'], 'logged out') !== false) {
    $success_message = urldecode($_GET['message']);
}
// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Initialize error variable
$error = '';
$username = '';
$lockout_message = '';
$remaining_seconds = 0;
$attempts_remaining = MAX_LOGIN_ATTEMPTS;

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input - check if fields are empty
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Query to find user by username or email
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];
            
            // Check if account is locked
            $lock_check = checkAccountLocked($user_id);
            
            if ($lock_check['locked']) {
                // Account is locked
                $remaining_seconds = $lock_check['remaining_time'];
                $minutes = ceil($remaining_seconds / 60);
                $error = 'Your account is temporarily locked. Please wait ' . $minutes . ' minute(s) and try again.';
                
                // Store in session for JavaScript countdown
                $_SESSION['lockout_until'] = $user['locked_until'];
                $_SESSION['show_lockout_timer'] = true;
            } else {
                // Account is not locked - check password
                if ($password == $user['password']) {
                    // Login successful - reset attempts
                    resetLoginAttempts($user_id);
                    
                    // Set session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['educator_mode'] = $user['educator_mode'];
                    $_SESSION['is_teacher'] = $user['is_teacher'];
                    
                    // Clear lockout session
                    unset($_SESSION['lockout_until']);
                    unset($_SESSION['show_lockout_timer']);
                    
                    // Redirect to prevent form resubmission
                    header('Location: index.php?success=1');
                    exit();
                } else {
                    // Password is incorrect - record failed attempt
                    $attempt_result = recordFailedAttempt($user_id);
                    
                    if ($attempt_result['locked']) {
                        // Account is now locked
                        $error = $attempt_result['message'];
                        $_SESSION['lockout_until'] = date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_DURATION . ' seconds'));
                        $_SESSION['show_lockout_timer'] = true;
                    } else {
                        // Show remaining attempts
                        $attempts_remaining = $attempt_result['attempts_remaining'];
                        $error = 'Invalid username or password. ' . $attempts_remaining . ' attempt(s) remaining.';
                    }
                }
            }
        } else {
            // User not found - for security, show generic message
            $error = 'Invalid username or password.';
        }
        $stmt->close();
    }
    
    // Clear POST data after processing
    if (isset($_POST['username']) && !isset($_SESSION['user_id'])) {
        $redirect_url = 'index.php';
        $params = array();
        
        if (!empty($error)) {
            $params[] = 'error=' . urlencode($error);
        }
        if (!empty($username)) {
            $params[] = 'username=' . urlencode($username);
        }
        
        if (!empty($params)) {
            $redirect_url .= '?' . implode('&', $params);
        }
        
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Get data from GET parameters (after redirect)
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

if (isset($_GET['username']) && !empty($_GET['username'])) {
    $username = urldecode($_GET['username']);
} else {
    $username = '';
}

// Check if we need to show lockout timer from session
if (isset($_SESSION['show_lockout_timer']) && isset($_SESSION['lockout_until'])) {
    $lockout_time = strtotime($_SESSION['lockout_until']);
    $now = time();
    $remaining_seconds = $lockout_time - $now;
    
    if ($remaining_seconds <= 0) {
        unset($_SESSION['lockout_until']);
        unset($_SESSION['show_lockout_timer']);
        if (strpos($error, 'locked') !== false) {
            $error = '';
        }
    }
}

// Clear username if no error
if (empty($error) && !isset($_GET['username']) && !isset($_SESSION['show_lockout_timer'])) {
    $username = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acadence Z - Login</title>
    
    <!-- Prevent browser from auto-filling fields -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
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
        
        .login-container {
            background: white;
            padding: 45px 40px 40px 40px;
            border-radius: 12px;
            box-shadow: 
                0 4px 6px rgba(0,0,0,0.07),
                0 10px 15px rgba(0,0,0,0.1),
                0 20px 40px rgba(0,0,0,0.15),
                0 0 0 1px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 420px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-header h1 {
            color: #333;
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        
        .login-header p {
            color: #888;
            font-size: 14px;
            letter-spacing: 0.2px;
        }
        
        /* ============================================
           FIX: Label and Input Styles
           ============================================ */
        
        /* Form group container */
        .form-group {
            margin-bottom: 22px;
        }
        
        /* Label styling */
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.3px;
        }
        
        /* Input field styling */
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #fafafa;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="password"]:focus {
            border-color: #8B5E3C;
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(139, 94, 60, 0.15);
        }
        
        .form-group input[type="text"]::placeholder,
        .form-group input[type="password"]::placeholder {
            color: #aaa;
            font-size: 14px;
        }
        
        .form-group input[type="text"]:disabled,
        .form-group input[type="password"]:disabled {
            background: #f0f0f0;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        /* Password wrapper */
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper input {
            width: 100%;
            padding: 12px 48px 12px 16px !important;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #fafafa;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .password-wrapper input:focus {
            border-color: #8B5E3C;
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(139, 94, 60, 0.15);
        }
        
        .password-wrapper input::placeholder {
            color: #aaa;
            font-size: 14px;
        }
        
        .password-wrapper input:disabled {
            background: #f0f0f0;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        /* Toggle password icon */
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            padding: 6px;
            border-radius: 6px;
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
        
        /* Password hint text */
        .password-hint {
            display: block;
            margin-top: 6px;
            color: #999;
            font-size: 12px;
            letter-spacing: 0.2px;
        }
        
        /* Lockout Timer Styles */
        .lockout-timer {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px 18px;
            margin-bottom: 20px;
        }
        
        .lockout-icon {
            font-size: 28px;
            color: #856404;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .lockout-content {
            flex: 1;
        }
        
        .lockout-title {
            font-weight: 600;
            color: #856404;
            font-size: 15px;
            margin-bottom: 2px;
        }
        
        .lockout-message {
            color: #856404;
            font-size: 14px;
        }
        
        .lockout-message #timerDisplay {
            font-weight: 700;
            font-size: 16px;
            color: #dc3545;
        }
        
        .lockout-progress {
            margin-top: 8px;
            background: #f1c40f;
            border-radius: 4px;
            height: 6px;
            overflow: hidden;
            background: rgba(255, 193, 7, 0.3);
        }
        
        .progress-bar {
            height: 100%;
            background: #f1c40f;
            border-radius: 4px;
            transition: width 1s linear;
            background: linear-gradient(90deg, #f1c40f, #e67e22);
        }
        
        /* Button styles */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #A67B5B 0%, #8B5E3C 50%, #5D4037 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.35);
        }
        
        .login-btn:disabled {
            background: #999;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        
        /* Error message */
        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .error-message .icon {
            font-size: 18px;
            color: #dc2626;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 14px;
            color: #888;
        }
        
        .login-footer a {
            color: #8B5E3C;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .login-footer a:hover {
            color: #A0522D;
            text-decoration: underline;
        }
        
        /* Responsive design */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .login-header h1 {
                font-size: 26px;
            }
            
            .form-group input[type="text"],
            .form-group input[type="password"],
            .password-wrapper input {
                padding: 11px 14px;
                font-size: 14px;
            }
        }
             /* Success message */
            .success-message {
                background: #ecfdf5;
                color: #065f46;
                padding: 12px 16px;
                border-radius: 8px;
                margin-bottom: 20px;
                border: 1px solid #a7f3d0;
                font-size: 14px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .success-message .icon {
            font-size: 18px;
            color: #065f46;
            font-weight: bold;
        }
    </style>
</head>
<body>

    

    <div class="login-container">
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <div class="login-header">
            <h1><i class="bi bi-book"></i> Acadence Z</h1>
            <p>Supplementary Interactive Learning System</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="index.php" id="loginForm" autocomplete="off">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username); ?>" 
                       placeholder="Enter your username or email" 
                       required 
                       <?php echo ($remaining_seconds > 0) ? 'disabled' : ''; ?>
                       autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" 
                           placeholder="Enter your password" 
                           required 
                           <?php echo ($remaining_seconds > 0) ? 'disabled' : ''; ?>
                           autocomplete="new-password">
                    <span class="toggle-password" id="togglePassword" title="Show Password" role="button" tabindex="0" aria-label="Show Password">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
                <small class="password-hint">Password must be at least 8 characters with uppercase, lowercase, number, and special character</small>
            </div>
            
            <?php if ($remaining_seconds > 0): ?>
            <div class="lockout-timer" id="lockoutTimer">
                <div class="lockout-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="lockout-content">
                    <div class="lockout-title">Account Temporarily Locked</div>
                    <div class="lockout-message">Too many failed login attempts. Please wait <span id="timerDisplay"><?php echo ceil($remaining_seconds / 60); ?>:<?php echo str_pad($remaining_seconds % 60, 2, '0', STR_PAD_LEFT); ?></span></div>
                    <div class="lockout-progress">
                        <div class="progress-bar" id="progressBar" style="width: <?php echo (($remaining_seconds / LOCKOUT_DURATION) * 100); ?>%;"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="login-btn" id="loginBtn" <?php echo ($remaining_seconds > 0) ? 'disabled' : ''; ?>>
                <?php echo ($remaining_seconds > 0) ? 'Please wait...' : 'Login'; ?>
            </button>
        </form>
        
        <div class="login-footer">
            <a href="forgot_password.php">Forgot Password?</a>
            <span style="margin: 0 10px;">|</span>
            <a href="register.php">Create Account</a>
        </div>
    </div>
    
    <script>
        (function() {
            'use strict';
            
            // ============================================
            // FIX: Clear form fields on page load
            // ============================================
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const hasError = urlParams.has('error');
                const hasSuccess = urlParams.has('success');
                const hasUsername = urlParams.has('username');
                
                if (!hasError && !hasSuccess && !hasUsername) {
                    const usernameInput = document.getElementById('username');
                    const passwordInput = document.getElementById('password');
                    
                    if (usernameInput && !usernameInput.disabled) {
                        usernameInput.value = '';
                    }
                    if (passwordInput && !passwordInput.disabled) {
                        passwordInput.value = '';
                    }
                }
                
                if (hasError && !hasUsername) {
                    const usernameInput = document.getElementById('username');
                    if (usernameInput && !usernameInput.disabled) {
                        usernameInput.value = '';
                    }
                }
                
                if (hasSuccess) {
                    const newUrl = window.location.pathname + window.location.search.replace(/[?&]success=1/, '');
                    window.history.replaceState({}, document.title, newUrl);
                }
                
                const usernameInput = document.getElementById('username');
                if (usernameInput && !usernameInput.disabled) {
                    setTimeout(function() {
                        usernameInput.focus();
                    }, 100);
                }
            });
            
            // ============================================
            // PASSWORD TOGGLE FUNCTIONALITY
            // ============================================
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            
            if (passwordInput && togglePassword) {
                const iconElement = togglePassword.querySelector('i');
                let isPasswordVisible = false;
                
                togglePassword.addEventListener('click', function() {
                    isPasswordVisible = !isPasswordVisible;
                    passwordInput.type = isPasswordVisible ? 'text' : 'password';
                    iconElement.className = isPasswordVisible ? 'bi bi-eye-slash' : 'bi bi-eye';
                    togglePassword.classList.toggle('active');
                });
            }
            
            // ============================================
            // LOCKOUT TIMER COUNTDOWN
            // ============================================
            const timerDisplay = document.getElementById('timerDisplay');
            const progressBar = document.getElementById('progressBar');
            const loginBtn = document.getElementById('loginBtn');
            const usernameInput = document.getElementById('username');
            const passwordInputField = document.getElementById('password');
            const lockoutTimer = document.getElementById('lockoutTimer');
            
            if (timerDisplay) {
                let totalSeconds = <?php echo $remaining_seconds; ?>;
                const startTime = Date.now();
                
                function updateTimer() {
                    const elapsed = (Date.now() - startTime) / 1000;
                    let remaining = Math.max(0, totalSeconds - elapsed);
                    
                    if (remaining > 0) {
                        const minutes = Math.floor(remaining / 60);
                        const seconds = Math.floor(remaining % 60);
                        timerDisplay.textContent = minutes + ':' + String(seconds).padStart(2, '0');
                        
                        const progressPercent = ((totalSeconds - remaining) / totalSeconds) * 100;
                        progressBar.style.width = Math.min(100, progressPercent) + '%';
                        
                        requestAnimationFrame(updateTimer);
                    } else {
                        timerDisplay.textContent = '0:00';
                        progressBar.style.width = '100%';
                        
                        usernameInput.disabled = false;
                        passwordInputField.disabled = false;
                        loginBtn.disabled = false;
                        loginBtn.textContent = 'Login';
                        
                        setTimeout(function() {
                            if (lockoutTimer) {
                                lockoutTimer.style.display = 'none';
                            }
                            const errorMsg = document.querySelector('.error-message');
                            if (errorMsg && errorMsg.textContent.includes('locked')) {
                                errorMsg.style.display = 'none';
                            }
                        }, 3000);
                    }
                }
                
                if (totalSeconds > 0) {
                    requestAnimationFrame(updateTimer);
                }
            }
            
            // ============================================
            // PREVENT DOUBLE SUBMISSION
            // ============================================
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    const btn = document.getElementById('loginBtn');
                    if (btn && !btn.disabled) {
                        btn.disabled = true;
                        btn.textContent = 'Logging in...';
                    }
                });
            }
            
            // ============================================
            // KEYBOARD SHORTCUT: Ctrl+Shift+P
            // ============================================
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.shiftKey && (e.key === 'P' || e.key === 'p')) {
                    e.preventDefault();
                    if (togglePassword) {
                        togglePassword.click();
                    }
                }
            });
            
        })();
    </script>
</body>
</html>

