<?php
// backend/get_event.php

session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Auth Guard Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['organizer', 'admin'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access container.']);
    exit;
}

require_once 'db.php';

// Validate requested integer parameter
$eventId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$eventId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing dynamic target sequence integer parameter.']);
    exit;
}

try {
    // Compile precise primary target record match
    $query = "SELECT e.*, u.username AS organizer_name, u.email AS organizer_email 
              FROM events e 
              LEFT JOIN users u ON e.organizer_id = u.id 
              WHERE e.id = ? LIMIT 1";
              
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$event) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Event index criteria matched no records.']);
        exit;
    }

    // Secondary Processing metrics allocations
    $regCount = 0;
    $regQ = "SELECT COUNT(*) as total FROM registrations WHERE event_id = ?";
    if ($s = $conn->prepare($regQ)) { $s->bind_param("i", $eventId); $s->execute(); $regCount = intval($s->get_result()->fetch_assoc()['total'] ?? 0); $s->close(); }

    $presentCount = 0;
    $attQ = "SELECT COUNT(*) as total FROM registrations WHERE event_id = ? AND attended = 1";
    if ($s = $conn->prepare($attQ)) { $s->bind_param("i", $eventId); $s->execute(); $presentCount = intval($s->get_result()->fetch_assoc()['total'] ?? 0); $s->close(); }

    $rates = [];
    $rateQ = "SELECT rating_score FROM feedback WHERE event_id = ? AND rating_score IS NOT NULL";
    if ($s = $conn->prepare($rateQ)) { $s->bind_param("i", $eventId); $s->execute(); $res = $s->get_result(); while($r = $res->fetch_assoc()){ $rates[] = intval($r['rating_score']); } $s->close(); }

    $reviews = [];
    $revQ = "SELECT comments FROM feedback WHERE event_id = ? AND comments IS NOT NULL AND comments != ''";
    if ($s = $conn->prepare($revQ)) { $s->bind_param("i", $eventId); $s->execute(); $res = $s->get_result(); while($r = $res->fetch_assoc()){ $reviews[] = $r['comments']; } $s->close(); }

    // Map structural data matching frontend schemas
    echo json_encode([
        'id'                   => intval($event['id']),
        'title'                => $event['title'],
        'description'          => $event['description'],
        'category'             => $event['category'] ?? 'Tech Development',
        'capacity'             => intval($event['capacity']),
        'startDate'            => $event['start_date'],
        'endDate'              => $event['end_date'],
        'registeredUsersCount' => $regCount,
        'presentUsersCount'    => $presentCount,
        'rates'                => $rates,
        'reviews'              => $reviews,
        'organizer' => [
            'name'  => $event['organizer_name'] ?? 'Core System Team',
            'email' => $event['organizer_email'] ?? 'admin@system.local'
        ],
        'venue' => [
            'name'    => $event['venue'] ?? 'Virtual Space Terminal',
            'address' => $event['venue_address'] ?? 'Cloud Instance Domain'
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}