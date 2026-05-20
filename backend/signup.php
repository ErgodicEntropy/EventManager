<?php
require 'db.php';
require_once '../classes/User.php';

// Instantiate the user object, passing the database connection dependency
$user = new User($conn);

// 1. Extract the new individual fields from your updated HTML form
// We use the null coalescing operator (?? '') to avoid "Undefined index" notices
$firstName = $_POST['firstName'] ?? '';
$lastName  = $_POST['lastName'] ?? '';
$username  = $_POST['username'] ?? '';
$email     = $_POST['email'] ?? '';
$password  = $_POST['password'] ?? '';
$role      = $_POST['role'] ?? ''; 

// 2. Pass ALL the required data variables into the fixed register() method
if ($user->register($firstName, $lastName, $username, $email, $password, $role)) {
    
    // 3. Handle your conditional routing loops cleanly based on the chosen role
    if ($role === "participant") {
        header("Location: ../frontend/participate.html");
        exit; // Always exit immediately after a header redirect!
    } elseif ($role === "organizer") {
        // Point this to your new central dashboard interface panel
        header("Location: ../frontend/panel.html");
        exit;
    }
    
    // Safety fallback routing if an unexpected role bypasses validation
    header("Location: ../frontend/start.html");
    exit;

} else {
    // 4. Fallback execution block if the database layer rejects the insert query
    echo "Registration failed. The username or email might already be taken.";
}
?>