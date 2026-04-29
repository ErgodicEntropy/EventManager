<?php
require 'db.php';
require_once '../classes/Event.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$event = new Event($conn);

$title = $_POST['title'];
$desc = $_POST['description'];
$date = $_POST['date'];
$location = $_POST['location'];

$event->create($title, $desc, $date, $location, $_SESSION['user_id']);

echo "Event created";
?>