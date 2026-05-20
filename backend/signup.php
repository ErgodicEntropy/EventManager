<?php
require 'db.php';
require_once '../classes/User.php';

$user = new User($conn);

$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];
$role = $_POST['role']; 


if ($user->register($name, $email, $password)) {
    echo "Registered successfully";
    if ($role == "participant"){
        header();
    }
    if ($role == "organizer"){
        header("Location: ../frontend/panel.html");
    }
    exit;
} else {
    echo "Registration failed";
}
?>