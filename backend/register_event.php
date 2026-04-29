<?php
require 'db.php';
require_once '../classes/Registration.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$registration = new Registration($conn);

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'];

if ($registration->register($user_id, $event_id)) {
    echo "Registered successfully";
} else {
    echo "Already registered or error";
}
?>