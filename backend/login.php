<?php
declare(strict_types=1);

require_once 'db.php'; // Assumes $conn is a valid mysqli instance
header('Content-Type: application/json; charset=utf-8');

session_start();

// 1. Structural Validation
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Please enter both your email address and password."
    ]);
    exit;
}

try {
    // 2. Locate User Profile by Email using MySQLi Prepared Statements
    $query = "SELECT id, password FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Statement preparation failed.");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // 3. Verify Account Matches and Validate Hash Check Lifecycle
    if ($user && password_verify($password, $user['password'])) {
        
        // Anti-session-hijacking protection layer
        session_regenerate_id(true);
        
        // Establish authentication variables
        $_SESSION['user_id'] = (int)$user['id'];

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Login successful. Redirecting to your workspace..."
        ]);

        // 3. Handle your conditional routing loops cleanly based on the chosen role
        if ($user['role'] === "participant") {
            header("Location: ../frontend/participate.html");
            exit; // Always exit immediately after a header redirect!
        } elseif ($user['role'] === "organizer") {
            // Point this to your new central dashboard interface panel
            header("Location: ../frontend/panel.html");
            exit;
        }

    } else {
        // Enforce generic response context for missing users vs wrong passwords to block email scouting
        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Invalid email credentials or password matching sequence."
        ]);
    }

    $stmt->close();
    header("Location: ../frontend/home.html"); 
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "An internal system authentication error occurred."
    ]);
}