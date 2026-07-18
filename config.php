<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'acadence_db';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Start session for login tracking
session_start();

// ========================================
// EMAIL CONFIGURATION (For Password Reset)
// ========================================
// Note: For local testing, we'll display the reset link directly
// In production, you would use PHPMailer or mail() function

// Function to generate a random reset token
function generateResetToken() {
    return bin2hex(random_bytes(32)); // 64 characters token
}

// Function to send password reset email
// For local testing - displays the reset link on screen
function sendResetEmail($email, $token) {
    // In production, send actual email using PHPMailer
    // For now, we store token in session to display on next page
    $_SESSION['reset_token_display'] = $token;
    $_SESSION['reset_email_display'] = $email;
    return true;
}

// Function to validate password strength
function isValidPassword($password) {
    // At least 8 characters
    if (strlen($password) < 8) {
        return array('valid' => false, 'message' => 'Password must be at least 8 characters long.');
    }
    // At least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return array('valid' => false, 'message' => 'Password must contain at least one uppercase letter.');
    }
    // At least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return array('valid' => false, 'message' => 'Password must contain at least one lowercase letter.');
    }
    // At least one number
    if (!preg_match('/[0-9]/', $password)) {
        return array('valid' => false, 'message' => 'Password must contain at least one number.');
    }
    // At least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return array('valid' => false, 'message' => 'Password must contain at least one special character.');
    }
    return array('valid' => true, 'message' => 'Password is strong.');
}

// ========================================
// SESSION MANAGEMENT CONFIGURATION
// ========================================

// Session timeout configuration
define('SESSION_TIMEOUT', 900);         // Session timeout in seconds (15 minutes = 900 seconds)

/**
 * Check if session has expired due to inactivity
 * 
 * @return bool True if session has expired, false otherwise
 */
function checkSessionTimeout() {
    if (!isset($_SESSION['last_activity'])) {
        // First activity - set the timestamp
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    $elapsed = time() - $_SESSION['last_activity'];
    return $elapsed > SESSION_TIMEOUT;
}

/**
 * Destroy the current session completely
 */
function destroySession() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    
    session_destroy();
}

/**
 * Regenerate session ID for security
 */
function regenerateSession() {
    // Regenerate session ID every 5 minutes to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 300) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// ========================================
// FAILED LOGIN ATTEMPTS CONFIGURATION
// ========================================

// Configuration constants
define('MAX_LOGIN_ATTEMPTS', 3);        // Maximum failed attempts allowed
define('LOCKOUT_DURATION', 120);         // Lockout duration in seconds (2 minutes = 120 seconds)

/**
 * Check if user account is locked
 * 
 * @param int $user_id The user ID
 * @return array ['locked' => bool, 'remaining_time' => int, 'message' => string]
 */
function checkAccountLocked($user_id) {
    global $conn;
    
    $sql = "SELECT login_attempts, locked_until FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $locked_until = $user['locked_until'];
        
        // Check if account is locked
        if ($locked_until !== null) {
            $now = time();
            $lock_time = strtotime($locked_until);
            
            if ($now < $lock_time) {
                // Account is still locked
                $remaining = $lock_time - $now;
                return [
                    'locked' => true,
                    'remaining_time' => $remaining,
                    'message' => 'Your account is temporarily locked. Please wait ' . ceil($remaining / 60) . ' minute(s) and try again.'
                ];
            } else {
                // Lockout period has expired - reset attempts
                resetLoginAttempts($user_id);
                return [
                    'locked' => false,
                    'remaining_time' => 0,
                    'message' => ''
                ];
            }
        }
        
        return [
            'locked' => false,
            'remaining_time' => 0,
            'message' => ''
        ];
    }
    $stmt->close();
    
    return [
        'locked' => false,
        'remaining_time' => 0,
        'message' => ''
    ];
}

/**
 * Record a failed login attempt
 * 
 * @param int $user_id The user ID
 * @return array ['locked' => bool, 'attempts_remaining' => int, 'message' => string]
 */
function recordFailedAttempt($user_id) {
    global $conn;
    
    // Get current attempts
    $sql = "SELECT login_attempts FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $current_attempts = $user['login_attempts'] + 1;
        $now = date('Y-m-d H:i:s');
        
        // Check if max attempts reached
        if ($current_attempts >= MAX_LOGIN_ATTEMPTS) {
            // Lock the account for 2 minutes
            $locked_until = date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_DURATION . ' seconds'));
            
            $update_sql = "UPDATE users SET login_attempts = ?, last_attempt_time = ?, locked_until = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('issi', $current_attempts, $now, $locked_until, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            return [
                'locked' => true,
                'attempts_remaining' => 0,
                'message' => 'Your account has been locked due to multiple failed login attempts. Please wait 2 minutes.'
            ];
        } else {
            // Just record the failed attempt
            $update_sql = "UPDATE users SET login_attempts = ?, last_attempt_time = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('isi', $current_attempts, $now, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $remaining = MAX_LOGIN_ATTEMPTS - $current_attempts;
            return [
                'locked' => false,
                'attempts_remaining' => $remaining,
                'message' => 'Invalid username or password. ' . $remaining . ' attempt(s) remaining before account lock.'
            ];
        }
    }
    $stmt->close();
    
    return [
        'locked' => false,
        'attempts_remaining' => MAX_LOGIN_ATTEMPTS,
        'message' => 'Invalid username or password.'
    ];
}

/**
 * Reset login attempts on successful login
 * 
 * @param int $user_id The user ID
 */
function resetLoginAttempts($user_id) {
    global $conn;
    
    $sql = "UPDATE users SET login_attempts = 0, last_attempt_time = NULL, locked_until = NULL WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Get remaining lockout time in seconds
 * 
 * @param int $user_id The user ID
 * @return int Remaining seconds, or 0 if not locked
 */
function getRemainingLockTime($user_id) {
    global $conn;
    
    $sql = "SELECT locked_until FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $locked_until = $user['locked_until'];
        
        if ($locked_until !== null) {
            $now = time();
            $lock_time = strtotime($locked_until);
            $remaining = $lock_time - $now;
            
            if ($remaining > 0) {
                return $remaining;
            }
        }
    }
    $stmt->close();
    
    return 0;
}
?>
