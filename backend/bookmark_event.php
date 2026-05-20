<?php
// backend/bookmark_event.php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Expired login access token.']);
    exit;
}

require_once 'db.php';
$userId = intval($_SESSION['user_id']);
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

if (!$eventId) {
    echo json_encode(['success' => false, 'message' => 'Integer reference argument missing.']);
    exit;
}

try {
    // Guard against creating duplicate bookmarks
    $checkQuery = "SELECT id FROM bookmarks WHERE user_id = ? AND event_id = ?";
    if ($stmt = $conn->prepare($checkQuery)) {
        $stmt->bind_param("ii", $userId, $eventId);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            echo json_encode(['success' => false, 'message' => 'This target array instance is already bookmarked.']);
            exit;
        }
    }

    // Insert new bookmark link row
    $insertQuery = "INSERT INTO bookmarks (user_id, event_id) VALUES (?, ?)";
    if ($stmt = $conn->prepare($insertQuery)) {
        $stmt->bind_param("ii", $userId, $eventId);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Bookmark matrix synchronized successfully.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}