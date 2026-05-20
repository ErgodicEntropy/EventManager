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

$venue_name    = trim($_POST['venue_name'] ?? ""); 
$venue_address = trim($_POST['venue_address'] ?? "");
$venue_capacity = isset($_POST['venue_capacity']) ? filter_var($_POST['venue_capacity'], FILTER_VALIDATE_INT): false;


if (empty($title) || empty($description) || empty($category) || !$capacity || empty($startInput) || empty($endInput) || empty($venue_name) || empty($venue_address) || empty($venue_capacity)) {
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
    $query = "INSERT INTO events (title, description, category, start_date, end_date, capacity, organizer_id, venue_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Sanitize string data against XSS
    $cleanTitle = htmlspecialchars($title);
    $cleanDesc  = htmlspecialchars($description);
    $cleanCat   = htmlspecialchars($category);

    $id; //check whether the event has already a venue

    $prep = $conn->prepare("SELECT * FROM venues"); 
    $prep->execute(); 
    $result = $prep->get_result();

    if ($result->num_rows > 0){
        $id = $result->num_rows + 1;
    } else {
        $id = 0; 
    }

    $stmt->bind_param("sssssii", $cleanTitle, $cleanDesc, $cleanCat, $formattedStart, $formattedEnd, $capacity, $organizerId, $id);
    $stmt->execute();

    $conn->query("INSERT INTO venues (id, name, address, capacity, is_available) VALUES($id, $venue_name, $venue_address, $venue_capacity, 1)");

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