<?php
// backend/get_events.php

// 1. Initialize session monitoring and output headers
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// 2. Authorization Guard
// Allows authenticated organizers or system administrators to monitor the registry indices
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['organizer', 'admin'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized data stream access request. Access denied.'
    ]);
    exit;
}

// 3. Import backend storage connectivity elements
require_once 'db.php'; // Your active connection handle ($conn)

try {
    // 4. Primary Query: Retrieve all core events along with their parent organizer names
    $eventQuery = "
        SELECT e.*, u.username AS organizer_name, u.email AS organizer_email
        FROM events e
        LEFT JOIN users u ON e.organizer_id = u.id
        ORDER BY e.start_date DESC
    ";

    $eventsArray = [];
    $eventResult = $conn->query($eventQuery);

    if (!$eventResult) {
        throw new Exception("Core registry compilation error: " . $conn->error);
    }

    // 5. Relational Sub-Query Iteration Mapping Loop
    while ($eventRow = $eventResult->fetch_assoc()) {
        $eventId = intval($eventRow['id']);

        // --- SUB-DATA TASK A: GET REGISTRATION FILL COUNTS ($registeredUsers) ---
        // Dynamically aggregates totals from the registration linkage table
        $regCount = 0;
        $regQuery = "SELECT COUNT(*) as total FROM registrations WHERE event_id = ?";
        if ($stmt = $conn->prepare($regQuery)) {
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $regCount = intval($res['total'] ?? 0);
            $stmt->close();
        }

        // --- SUB-DATA TASK B: GET ATTENDANCE STATUS COUNTS ($presentUsers) ---
        // Aggregates users who have successfully checked into the event instance
        $presentCount = 0;
        $attendanceQuery = "SELECT COUNT(*) as total FROM registrations WHERE event_id = ? AND attended = 1";
        if ($stmt = $conn->prepare($attendanceQuery)) {
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $presentCount = intval($res['total'] ?? 0);
            $stmt->close();
        }

        // --- SUB-DATA TASK C: PARSE METRIC RATING RAW INTEGER ARRAYS ($rates) ---
        $rates = [];
        $ratingQuery = "SELECT rating_score FROM feedback WHERE event_id = ? AND rating_score IS NOT NULL";
        if ($stmt = $conn->prepare($ratingQuery)) {
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($rateRow = $res->fetch_assoc()) {
                $rates[] = intval($rateRow['rating_score']);
            }
            $stmt->close();
        }

        // --- SUB-DATA TASK D: COLLECT EVALUATION TEXT STRINGS ($reviews) ---
        $reviews = [];
        $reviewQuery = "SELECT comments FROM feedback WHERE event_id = ? AND comments IS NOT NULL AND comments != ''";
        if ($stmt = $conn->prepare($reviewQuery)) {
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($revRow = $res->fetch_assoc()) {
                $reviews[] = $revRow['comments'];
            }
            $stmt->close();
        }

        // 6. Build the Nested Schema Object to fit the JavaScript Stream parser
        $eventsArray[] = [
            'id'                  => $eventId,
            'title'               => $eventRow['title'],
            'description'         => $eventRow['description'],
            'category'            => $eventRow['category'] ?? 'General Tech',
            'capacity'            => intval($eventRow['capacity']),
            'startDate'           => $eventRow['start_date'],
            'endDate'             => $eventRow['end_date'],
            'registeredUsersCount'=> $regCount,
            'presentUsersCount'   => $presentCount,
            'rates'               => $rates,
            'reviews'             => $reviews,
            'organizer'           => [
                'name'  => $eventRow['organizer_name'] ?? 'System Core Matrix',
                'email' => $eventRow['organizer_email'] ?? 'admin@system.local'
            ],
            'venue'               => [
                'name'        => $eventRow['venue'] ?? 'Virtual Space Tower',
                'address'     => $eventRow['venue_address'] ?? 'Cloud Instance Gateway',
                'capacity'    => intval($eventRow['capacity']),
                'isAvailable' => true
            ]
        ];
    }

    // 7. Render structural arrays out to the client console stream container
    echo json_encode($eventsArray);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Internal abstraction mapping cluster failure: ' . $e->getMessage()
    ]);
}
exit;