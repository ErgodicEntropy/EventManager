<?php
declare(strict_types=1);

require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');

// 1. Input Collection & Validation
$eventId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : false;

if (!$eventId) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or missing parameters: Event ID must be an integer."
    ]);
    exit;
}

try {
    // 2. Prepare Structured Query matching the core Event class schema
    $query = "SELECT 
                e.id, 
                e.title, 
                e.description, 
                e.category, 
                e.start_date AS startDate, 
                e.end_date AS endDate, 
                e.capacity,
                u.name AS organizer
              FROM events e
              LEFT JOIN users u ON e.organizer_id = u.id
              WHERE e.id = ?";
              
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Statement preparation failed.");
    }

    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    
    // Get results directly into associative array style with get_result()
    $result = $stmt->get_result();
    $eventData = $result->fetch_assoc();

    // 3. Verify Record Existence Guard
    if (!$eventData) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "The requested event could not be found in this workspace."
        ]);
        $stmt->close();
        exit;
    }

    // 4. Return Normalized Data Payload
    http_response_code(200);
    echo json_encode($eventData);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Internal workspace data transmission failure."
    ]);
}

?>