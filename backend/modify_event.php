<?php
// backend/modify_event.php

// 1. Initialize session monitoring and header layouts
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 2. Access Authorization Guard
// Verifies that a user is logged in and possesses the correct administrative role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized entry attempt. Invalid organizer context.'
    ]);
    exit;
}

// 3. Structural Import Requirements
require_once 'db.php'; // Pulls your active database connection variable ($conn)

// 4. Intercept and Sanitize Incoming POST Parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Extract integers safely using filter_var or intval
    $eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $organizerId = filter_input(INPUT_POST, 'organizer_id', FILTER_VALIDATE_INT);
    $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);

    // Clean text strings against raw injection formatting strings
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $venue_name    = trim($_POST['venue_name'] ?? ""); 
    $venue_address = trim($_POST['venue_address'] ?? "");
    $venue_capacity = isset($_POST['venue_capacity']) ? filter_var($_POST['venue_capacity'], FILTER_VALIDATE_INT): false;
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    
    // Date/Time Strings
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';

    // Validate absolute requirement fields
    if (!$eventId || !$title || !$venue || !$capacity || !$startDate || !$endDate || $venue_name || $venue_address || $venue_capacity) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed payload validation. Missing required configuration arguments.'
        ]);
        exit;
    }

    // Logic Guard: Ensure timeline configuration makes chronological sense
    if (strtotime($startDate) >= strtotime($endDate)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Scheduling anomaly: Event End Date must occur after the Start Date.'
        ]);
        exit;
    }

    try {
        // 5. Construct Prepared Statement Update Pipeline
        // Scoped securely by event_id AND organizer_id to prevent multi-tenant cross-modification vulnerabilities
        $updateQuery = "
            UPDATE events 
            SET title = ?, 
                description = ?, 
                category = ?, 
                capacity = ?, 
                start_date = ?, 
                end_date = ? 
            WHERE id = ? AND organizer_id = ?
        ";

        $venue_id;
        
        $preparedStatement = $conn->prepare("SELECT venue_id FROM events where id=$eventId"); 
        $preparedStatement->execute();
        $result = $preparedStatement->get_result(); 
        if ($result->num_rows > 0){
            $venue_id = $result;
        } else{
            $venue_id = NULL;
        }
        
        
        $conn->query("
            UPDATE venues 
            SET name = $venue_name, address = $venue_address, capacity = $venue_capacity
            WHERE id = $venue_id 
        ");

        if ($stmt = $conn->prepare($updateQuery)) {
            // Bind input values cleanly: s = string, i = integer
            $stmt->bind_param(
                "sssissii", 
                $title, 
                $description, 
                $category, 
                $capacity, 
                $startDate, 
                $endDate, 
                $eventId, 
                $_SESSION['user_id'] // Overrides form-spoofing by using real session variable
            );

            if ($stmt->execute()) {
                // Check if a row actually matched criteria and updated
                if ($stmt->affected_rows >= 0) {
                    // Redirect back to dashboard panel layout or return JSON matching API specifications
                    // If you want a direct browser redirect instead of an API response, uncomment the line below:
                    // header("Location: ../frontend/panel.html?update=success"); exit;
                    
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Event registry matrix attributes modified successfully.'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Target database record not found or no structural modifications detected.'
                    ]);
                }
            } else {
                throw new Exception($stmt->error);
            }
            $stmt->close();
        } else {
            throw new Exception($conn->error);
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Internal storage compilation engine exception: ' . $e->getMessage()
        ]);
    }
} else {
    // Decline requests outside POST method channels
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'HTTP Method Not Allowed.'
    ]);
}
exit;
?>