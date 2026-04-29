<?php
require 'db.php';
session_start();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM events WHERE organizer_id = ?");
$stmt->execute([$user_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>