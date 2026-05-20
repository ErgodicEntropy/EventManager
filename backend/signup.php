<?php
require 'db.php';
require_once '../classes/User.php';

// Create User service with DB connection dependency
$user = new User($conn);

// -------------------------------
// 1. Collect and sanitize input
// -------------------------------
$firstName = trim($_POST['firstName'] ?? '');
$lastName  = trim($_POST['lastName'] ?? '');
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$role      = $_POST['role'] ?? 'participant';

// -------------------------------
// 2. Basic validation (prevent empty inserts)
// -------------------------------
if (!$firstName || !$lastName || !$username || !$email || !$password) {
    die("All fields are required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email format.");
}

// -------------------------------
// 3. Attempt registration safely
// -------------------------------
try {
    $success = $user->register(
        $firstName,
        $lastName,
        $username,
        $email,
        $password,
        $role
    );

    // -------------------------------
    // 4. Handle result from User class
    // -------------------------------
    if ($success) {

        // Role-based routing after successful signup
        if ($role === "participant") {
            header("Location: ../frontend/participate.html");
            exit;
        }

        if ($role === "organizer") {
            header("Location: ../frontend/panel.html");
            exit;
        }

        // Fallback route
        header("Location: ../frontend/start.html");
        exit;

    } else {
        // This happens when User::register detects duplicate BEFORE insert (if you implemented pre-check)
        echo "Username or email already exists. Please choose another.";
    }

} catch (mysqli_sql_exception $e) {

    // ---------------------------------------------------
    // FIX FOR YOUR ORIGINAL PROBLEM:
    // Duplicate entry error (MySQL error code 1062)
    // ---------------------------------------------------
    if ($e->getCode() === 1062) {
        echo "Registration failed: username or email already exists.";
        exit;
    }

    // Any other unexpected database error
    error_log("Signup error: " . $e->getMessage());
    die("An unexpected error occurred. Please try again later.");
}
?>