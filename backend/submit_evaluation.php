<?php
// backend/submit_evaluation.php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authorized token unverified.']);
    exit;
}

require_once 'db.php';
$userId = intval($_SESSION['user_id']);

// Capture processing arguments
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$rating  = filter_input(INPUT_POST, 'rate', FILTER_VALIDATE_INT);
$comment = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$eventId || !$rating || !$comment) {
    echo json_encode(['success' => false, 'message' => 'All validation evaluation arguments must be defined.']);
    exit;
}

// Ensure the score values bounds are valid
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Score outside allowable rating scope ranges (1-5).']);
    exit;
}

try {
    // Verify the user actually attended this instance before allowing reviews
    $verifyQuery = "SELECT id FROM registrations WHERE user_id = ? AND event_id = ?";
    if ($stmt = $conn->prepare($verifyQuery)) {
        $stmt->bind_param("ii", $userId, $eventId);
        $stmt->execute();
        $hasRegistration = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$hasRegistration) {
            echo json_encode(['success' => false, 'message' => 'Evaluation locked. You must be a registered participant of this event.']);
            exit;
        }
    }

    // Write metric allocations synchronously into the feedback layer database tables
    $feedbackQuery = "INSERT INTO feedback (event_id, user_id, rating_score, comments) 
                      VALUES (?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE rating_score = ?, comments = ?";
                      
    if ($stmt = $conn->prepare($feedbackQuery)) {
        // Parameters order mapping: event, user, score, text comment, duplicate update score, duplicate update text comment
        $stmt->bind_param("iiisid", $eventId, $userId, $rating, $comment, $rating, $comment);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Evaluation payload synchronized inside application instances layer.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feedback database insertion sequence error: ' . $e->getMessage()]);
}