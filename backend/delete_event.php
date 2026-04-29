<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'];

// ensure ownership
$stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?");
$stmt->execute([$event_id, $user_id]);

echo "Event deleted";
?>