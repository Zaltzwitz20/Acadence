<?php
// Include database connection
include 'config.php';

// ============================================
// SESSION MANAGEMENT CHECK
// ============================================

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=' . urlencode('Session expired. Please login again.'));
    exit();
}

// Check if session has expired due to inactivity
if (checkSessionTimeout()) {
    destroySession();
    header('Location: index.php?error=' . urlencode('Session expired due to inactivity. Please login again.'));
    exit();
}

// Regenerate session ID periodically for security
regenerateSession();

// Get user info
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$username = $_SESSION['username'];

// ============================================
// MANUAL LOGOUT
// ============================================
if (isset($_GET['logout'])) {
    destroySession();
    header('Location: index.php?message=' . urlencode('You have been logged out successfully.'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acadence Z - Dashboard</title>
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
            background: url('img/dashboard_background.jpg') center center fixed no-repeat;
            background-size: cover;
            background-color: #f0f2f5;
        }
        
        .navbar {
            background: linear-gradient(135deg, #A67B5B 0%, #8B5E3C 50%, #5D4037 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar .brand h2 {
            font-size: 22px;
        }
        
        .navbar .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .navbar .user-info .user-icon {
            font-size: 20px;
        }
        
        .navbar .session-timer {
            font-size: 13px;
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .navbar .session-timer .time {
            font-weight: 600;
            min-width: 45px;
        }
        
        .navbar .session-timer.warning {
            background: rgba(255, 193, 7, 0.4);
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .navbar .logout-btn {
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }
        
        .navbar .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 
                0 2px 4px rgba(0,0,0,0.06),
                0 6px 12px rgba(0,0,0,0.08),
                0 12px 24px rgba(0,0,0,0.1),
                0 0 0 1px rgba(0,0,0,0.04);
            margin-bottom: 30px;
        }
        
        .welcome-card h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
            font-size: 16px;
        }
        
        .welcome-card .session-info {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #8B5E3C;
            font-size: 14px;
            color: #555;
        }
        
        .welcome-card .session-info .bi {
            color: #8B5E3C;
            margin-right: 8px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 
                0 2px 4px rgba(0,0,0,0.06),
                0 6px 12px rgba(0,0,0,0.08),
                0 12px 24px rgba(0,0,0,0.1),
                0 0 0 1px rgba(0,0,0,0.04);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .card .icon {
            font-size: 40px;
            margin-bottom: 10px;
            color: #8B5E3C;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 20px;
            background: #8B4513;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #A0522D;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
                padding: 12px 20px;
            }
            
            .navbar .nav-right {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand">
            <h2><i class="bi bi-book"></i> Acadence Z</h2>
        </div>
        <div class="nav-right">
            <div class="user-info">
                <span class="user-icon"><i class="bi bi-person"></i></span>
                <span><?php echo htmlspecialchars($full_name); ?></span>
            </div>
            <div class="session-timer" id="sessionTimer">
                <i class="bi bi-clock"></i>
                <span>Session: </span>
                <span class="time" id="sessionTime">15:00</span>
            </div>
            <a href="dashboard.php?logout=1" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h1><i class="bi bi-hand-wave"></i> Welcome, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <p>Your supplementary interactive learning system</p>
            <div class="session-info">
                <i class="bi bi-info-circle"></i>
                Your session will automatically expire after <strong>15 minutes</strong> of inactivity. 
                The timer will reset with each activity.
            </div>
        </div>
        
        <div class="grid">
            <div class="card">
                <div class="icon"><i class="bi bi-book"></i></div>
                <h3>My Lessons</h3>
                <p>View and access your enrolled lessons</p>
                <a href="#" class="btn">View Lessons</a>
            </div>
            
            <div class="card">
                <div class="icon"><i class="bi bi-bar-chart"></i></div>
                <h3>Progress</h3>
                <p>Track your learning progress</p>
                <a href="#" class="btn">View Progress</a>
            </div>
            
            <div class="card">
                <div class="icon"><i class="bi bi-trophy"></i></div>
                <h3>Performance</h3>
                <p>Check your performance metrics</p>
                <a href="#" class="btn">View Reports</a>
            </div>
        </div>
    </div>
    
    <script>
        (function() {
            'use strict';
            
            // ============================================
            // SESSION TIMER
            // ============================================
            const SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT; ?>; // 15 minutes in seconds
            let remainingTime = SESSION_TIMEOUT;
            const timerDisplay = document.getElementById('sessionTime');
            const timerContainer = document.getElementById('sessionTimer');
            let timerInterval = null;
            
            // Format time as MM:SS
            function formatTime(seconds) {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
            }
            
            // Update the timer display
            function updateTimer() {
                if (remainingTime <= 0) {
                    // Session expired - redirect to login
                    clearInterval(timerInterval);
                    window.location.href = 'index.php?error=' + encodeURIComponent('Session expired due to inactivity. Please login again.');
                    return;
                }
                
                timerDisplay.textContent = formatTime(remainingTime);
                
                // Add warning class when less than 60 seconds
                if (remainingTime <= 60) {
                    timerContainer.classList.add('warning');
                } else {
                    timerContainer.classList.remove('warning');
                }
                
                remainingTime--;
            }
            
            // Reset the timer on user activity
            function resetTimer() {
                remainingTime = SESSION_TIMEOUT;
                
                // Send AJAX request to update session activity
                fetch('update_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=update_activity'
                }).catch(function(error) {
                    // Silent fail - don't disrupt user experience
                });
            }
            
            // Start the timer
            function startTimer() {
                if (timerInterval) {
                    clearInterval(timerInterval);
                }
                remainingTime = SESSION_TIMEOUT;
                timerInterval = setInterval(updateTimer, 1000);
            }
            
            // ============================================
            // TRACK USER ACTIVITY
            // ============================================
            // List of events that indicate user activity
            const activityEvents = [
                'click', 'mousemove', 'keydown', 'scroll', 
                'touchstart', 'touchmove', 'wheel'
            ];
            
            // Debounce function to prevent too many updates
            let activityTimeout = null;
            
            function handleActivity() {
                // Clear any pending timeout
                if (activityTimeout) {
                    clearTimeout(activityTimeout);
                }
                
                // Only reset after a short delay to prevent excessive updates
                activityTimeout = setTimeout(function() {
                    resetTimer();
                    activityTimeout = null;
                }, 500);
            }
            
            // Add event listeners for user activity
            activityEvents.forEach(function(event) {
                document.addEventListener(event, handleActivity);
            });
            
            // Also track visibility change (tab focus)
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    // User came back to the tab - check if session is still valid
                    handleActivity();
                }
            });
            
            // ============================================
            // START THE TIMER
            // ============================================
            startTimer();
            
        })();
    </script>
</body>
</html>





