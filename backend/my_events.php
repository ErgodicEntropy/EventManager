<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT events.*
    FROM events
    JOIN registrations ON events.id = registrations.event_id
    WHERE registrations.user_id = ?
");

$stmt->execute([$user_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>