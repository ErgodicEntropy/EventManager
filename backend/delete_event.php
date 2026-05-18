<?php
declare(strict_types=1);

require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

session_start();

// 1. Authorization Guard
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized access. Please log in."
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// 2. Input Collection & Validation
$eventId = isset($_POST['event_id']) ? filter_var($_POST['event_id'], FILTER_VALIDATE_INT) : false;

if (!$eventId) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or missing Event ID parameter."
    ]);
    exit;
}

try {
    // 3. MySQLi Prepared Statement with Ownership Restriction
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?");
    
    if (!$stmt) {
        throw new Exception("Statement preparation failed.");
    }

    $stmt->bind_param("ii", $eventId, $userId);
    $stmt->execute();

    // 4. Verify Row Was Actually Deleted (Enforces True Ownership)
    if ($stmt->affected_rows === 0) {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Action forbidden, or event does not exist in your workspace."
        ]);
        $stmt->close();
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Event was successfully deleted from the workspace."
    ]);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database transaction failed: Secure deletion aborted."
    ]);
}

?>