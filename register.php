<?php
// Include database connection
include 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';
$full_name = '';
$username = '';
$email = '';

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Validate password strength
        $password_validation = isValidPassword($password);
        if (!$password_validation['valid']) {
            $error = $password_validation['message'];
        } else {
            // Check if username already exists
            $sql = "SELECT user_id FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Username already exists. Please choose a different username.';
            } else {
                $stmt->close();
                
                // Check if email already exists
                $sql = "SELECT user_id FROM users WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = 'Email already exists. Please use a different email address.';
                } else {
                    $stmt->close();
                    
                    // Insert new user
                    $sql = "INSERT INTO users (full_name, username, email, password, educator_mode, is_teacher, login_attempts, created_at) 
                            VALUES (?, ?, ?, ?, 0, 0, 0, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ssss', $full_name, $username, $email, $password);
                    
                    if ($stmt->execute()) {
                        $success = 'Registration successful! You can now login with your credentials.';
                        // Clear form data
                        $full_name = '';
                        $username = '';
                        $email = '';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acadence Z - Create Account</title>
    
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
        
        .register-container {
            background: white;
            padding: 45px 40px 40px 40px;
            border-radius: 12px;
            box-shadow: 
                0 4px 6px rgba(0,0,0,0.07),
                0 10px 15px rgba(0,0,0,0.1),
                0 20px 40px rgba(0,0,0,0.15),
                0 0 0 1px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 450px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .register-header h1 {
            color: #333;
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        
        .register-header p {
            color: #888;
            font-size: 14px;
            letter-spacing: 0.2px;
        }
        
        /* Form group container */
        .form-group {
            margin-bottom: 20px;
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
        .form-group input[type="email"],
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
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            border-color: #8B5E3C;
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(139, 94, 60, 0.15);
        }
        
        .form-group input[type="text"]::placeholder,
        .form-group input[type="email"]::placeholder,
        .form-group input[type="password"]::placeholder {
            color: #aaa;
            font-size: 14px;
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
        
        /* Password Requirements */
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
        
        /* Button styles */
        .register-btn {
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
            margin-top: 8px;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.35);
        }
        
        .register-btn:disabled {
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
        
        .register-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 14px;
            color: #888;
        }
        
        .register-footer a {
            color: #8B5E3C;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .register-footer a:hover {
            color: #A0522D;
            text-decoration: underline;
        }
        
        /* Responsive design */
        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .register-header h1 {
                font-size: 26px;
            }
            
            .form-group input[type="text"],
            .form-group input[type="email"],
            .form-group input[type="password"],
            .password-wrapper input {
                padding: 11px 14px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <div class="register-container">
        <div class="register-header">
            <h1><i class="bi bi-book"></i> Acadence Z</h1>
            <p>Create Your Account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="bi bi-exclamation-triangle-fill icon"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <i class="bi bi-check-circle-fill icon"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="register.php" id="registerForm" autocomplete="off">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo htmlspecialchars($full_name); ?>" 
                       placeholder="Enter your full name" 
                       required 
                       autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($username); ?>" 
                       placeholder="Choose a username" 
                       required 
                       autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email); ?>" 
                       placeholder="Enter your email address" 
                       required 
                       autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" 
                           placeholder="Create a password" 
                           required 
                           autocomplete="new-password">
                    <span class="toggle-password" id="togglePassword" title="Show Password" role="button" tabindex="0" aria-label="Show Password">
                        <i class="bi bi-eye"></i>
                    </span>
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
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm your password" 
                           required 
                           autocomplete="new-password">
                    <span class="toggle-password" id="toggleConfirmPassword" title="Show Password" role="button" tabindex="0" aria-label="Show Password">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="register-btn" id="registerBtn">
                Create Account
            </button>
        </form>
        
        <div class="register-footer">
            Already have an account? <a href="index.php">Login here</a>
        </div>
    </div>
    
    <script>
        (function() {
            'use strict';
            
            // ============================================
            // PASSWORD TOGGLE FUNCTIONALITY
            // ============================================
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            
            function setupPasswordToggle(input, toggle, iconElement) {
                let isPasswordVisible = false;
                
                toggle.addEventListener('click', function() {
                    isPasswordVisible = !isPasswordVisible;
                    input.type = isPasswordVisible ? 'text' : 'password';
                    iconElement.className = isPasswordVisible ? 'bi bi-eye-slash' : 'bi bi-eye';
                    toggle.classList.toggle('active');
                });
            }
            
            if (passwordInput && togglePassword) {
                const iconElement = togglePassword.querySelector('i');
                setupPasswordToggle(passwordInput, togglePassword, iconElement);
            }
            
            if (confirmPasswordInput && toggleConfirmPassword) {
                const iconElement = toggleConfirmPassword.querySelector('i');
                setupPasswordToggle(confirmPasswordInput, toggleConfirmPassword, iconElement);
            }
            
            // ============================================
            // PREVENT DOUBLE SUBMISSION
            // ============================================
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function() {
                    const btn = document.getElementById('registerBtn');
                    if (btn && !btn.disabled) {
                        btn.disabled = true;
                        btn.textContent = 'Creating account...';
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
            
            // ============================================
            // PASSWORD VALIDATION
            // ============================================
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
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    validatePassword(this.value);
                    
                    // Also check if passwords match
                    if (confirmPasswordInput.value && this.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.style.borderColor = '#dc3545';
                    } else if (confirmPasswordInput.value) {
                        confirmPasswordInput.style.borderColor = '#28a745';
                    }
                });
            }
            
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    if (passwordInput.value && this.value !== passwordInput.value) {
                        this.style.borderColor = '#dc3545';
                    } else if (passwordInput.value && this.value === passwordInput.value) {
                        this.style.borderColor = '#28a745';
                    }
                });
            }
            
            // Form validation before submit
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    const password = passwordInput ? passwordInput.value : '';
                    const confirm = confirmPasswordInput ? confirmPasswordInput.value : '';
                    
                    if (!validatePassword(password)) {
                        e.preventDefault();
                        alert('Please meet all password requirements.');
                        return false;
                    }
                    
                    if (password !== confirm) {
                        e.preventDefault();
                        alert('Passwords do not match.');
                        if (confirmPasswordInput) {
                            confirmPasswordInput.focus();
                        }
                        return false;
                    }
                });
            }
            
        })();
    </script>
</body>
</html>
