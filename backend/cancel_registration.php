<?php
// backend/cancel_registration.php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session identification dropped.']);
    exit;
}

require_once 'db.php';
$userId = intval($_SESSION['user_id']);
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

if (!$eventId) {
    echo json_encode(['success' => false, 'message' => 'Malformed parameter schema.']);
    exit;
}

try {
    // Execute target query deletion block
    $deleteQuery = "DELETE FROM registrations WHERE user_id = ? AND event_id = ?";
    if ($stmt = $conn->prepare($deleteQuery)) {
        $stmt->bind_param("ii", $userId, $eventId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            // Optional: Also clean up related event ticket objects if applicable
            $stmtTkt = $conn->prepare("DELETE FROM tickets WHERE user_id = ? AND event_id = ?");
            $stmtTkt->bind_param("ii", $userId, $eventId);
            $stmtTkt->execute();
            $stmtTkt->close();

            echo json_encode(['success' => true, 'message' => 'Registration sequence successfully reversed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No active registration parameters found matching criteria.']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}