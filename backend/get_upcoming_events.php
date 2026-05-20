<?php
// backend/get_upcoming_events.php

// 1. Initialize session and system headers
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 2. Authorization Guard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'participant') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized session access state.']);
    exit;
}

// 3. Import dependencies
require_once 'db.php'; // Your mysqli connection script ($conn)
require_once '../classes/User.php';
require_once '../classes/Participant.php';

$userId = intval($_SESSION['user_id']);

try {
    // 4. Initialize the Participant Object
    $participant = new Participant($conn);
    $participant->id = $userId;

    // --- TASK A: RETRIEVE REGISTERED EVENTS ($registeredEvents) ---
    // Querying the associative table mapping participants to their events
    $registeredQuery = "
        SELECT e.id, e.title, e.category, e.start_date 
        FROM registrations r
        JOIN events e ON r.event_id = e.id
        WHERE r.user_id = ? AND e.start_date >= NOW()
        ORDER BY e.start_date ASC
    ";
    
    $registeredEvents = [];
    if ($stmt = $conn->prepare($registeredQuery)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $registeredEvents[] = [
                'id'       => intval($row['id']),
                'title'    => $row['title'],
                'category' => $row['category'] ?? 'Event',
                'date'     => date('M d, Y', strtotime($row['start_date']))
            ];
        }
        $stmt->close();
    }

    // --- TASK B: RETRIEVE BOOKMARKED EVENTS ($favoriteEvents) ---
    $bookmarkQuery = "
        SELECT e.id, e.title, e.category 
        FROM bookmarks b
        JOIN events e ON b.event_id = e.id
        WHERE b.user_id = ?
        ORDER BY b.id DESC
    ";
    
    $favoriteEvents = [];
    if ($stmt = $conn->prepare($bookmarkQuery)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $favoriteEvents[] = [
                'id'       => intval($row['id']),
                'title'    => $row['title'],
                'category' => $row['category'] ?? 'General'
            ];
        }
        $stmt->close();
    }

    // --- TASK C: RETRIEVE ASSIGNED TICKET PASS ---
    // Grabs the nearest upcoming event ticket to display in the UI widget
    $ticketQuery = "
        SELECT t.serial_number, t.tier_name, e.title AS event_title
        FROM tickets t
        JOIN events e ON t.event_id = e.id
        WHERE t.user_id = ? AND e.start_date >= NOW()
        LIMIT 1
    ";
    
    $ticketData = null;
    if ($stmt = $conn->prepare($ticketQuery)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $ticketData = [
                'serialNumber' => $row['serial_number'],
                'tierName'     => $row['tier_name'],
                'eventTitle'   => $row['event_title']
            ];
        }
        $stmt->close();
    }

    // 5. Package up structural objects and dispatch
    echo json_encode([
        'status'     => 'success',
        'registered' => $registeredEvents,
        'favorites'  => $favoriteEvents,
        'ticket'     => $ticketData
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Internal server compilation error matching dataset queries.'
    ]);
}
exit;