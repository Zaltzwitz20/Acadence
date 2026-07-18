<?php
/**
 * update_session.php
 * AJAX endpoint to update session activity
 * Called by JavaScript when user is active
 */

include 'config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Check if session is already expired
if (checkSessionTimeout()) {
    destroySession();
    http_response_code(401);
    exit('Session Expired');
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Return success response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Session activity updated'
]);
?>