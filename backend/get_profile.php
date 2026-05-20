<?php
// backend/get_profile.php

// 1. Initialize the session engine to catch current user data
session_start();

// 2. Set headers to ensure the browser interprets this strictly as a JSON payload
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 3. Authorization Guard: Verify if the user session is active
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Return a 401 Unauthorized status code if no valid session data exists
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthenticated session context. Please log in.'
    ]);
    exit;
}

// 4. Dispatch the authenticated identity metrics to your client-side JavaScript
// Feel free to add more session properties here if needed (like 'role' or 'email')
echo json_encode([
    'status'   => 'success',
    'user_id'  => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role'     => $_SESSION['role'] ?? 'participant'
]);
exit;