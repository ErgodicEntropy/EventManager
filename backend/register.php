<?php
require 'db.php';
require_once '../classes/User.php';

$user = new User($conn);

$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];

if ($user->register($name, $email, $password)) {
    echo "Registered successfully";
} else {
    echo "Registration failed";
}
?>