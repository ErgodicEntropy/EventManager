<?php
declare(strict_types=1);

require_once 'db.php'; // Assumes $conn is a valid mysqli object
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

$organizerId = (int)$_SESSION['user_id'];

// 2. Strict Input Collection & Validation
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');
$capacity    = isset($_POST['capacity']) ? filter_var($_POST['capacity'], FILTER_VALIDATE_INT) : false;
$startInput  = trim($_POST['startDate'] ?? '');
$endInput    = trim($_POST['endDate'] ?? '');

if (empty($title) || empty($description) || empty($category) || !$capacity || empty($startInput) || empty($endInput)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required and must contain valid values."
    ]);
    exit;
}

try {
    // 3. Process Dates
    $startDate = new DateTime($startInput);
    $endDate   = new DateTime($endInput);

    if ($endDate <= $startDate) {
        throw new Exception("The event end date must occur after the start date.");
    }

    $formattedStart = $startDate->format('Y-m-d H:i:s');
    $formattedEnd   = $endDate->format('Y-m-d H:i:s');

    // 4. MySQLi Prepared Statement
    $query = "INSERT INTO events (title, description, category, start_date, end_date, capacity, organizer_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Sanitize string data against XSS
    $cleanTitle = htmlspecialchars($title);
    $cleanDesc  = htmlspecialchars($description);
    $cleanCat   = htmlspecialchars($category);

    $stmt->bind_param("sssssii", $cleanTitle, $cleanDesc, $cleanCat, $formattedStart, $formattedEnd, $capacity, $organizerId);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Failed to persist event record.");
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Workspace event published successfully."
    ]);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server transaction aborted: " . $e->getMessage()
    ]);
}

?>