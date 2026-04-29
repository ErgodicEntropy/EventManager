<?php
require 'db.php';
require_once '../classes/User.php';

session_start();

$userObj = new User($conn);

$email = $_POST['email'];
$password = $_POST['password'];

$user = $userObj->login($email, $password);

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    echo "Login successful";
} else {
    echo "Invalid credentials";
}
?>