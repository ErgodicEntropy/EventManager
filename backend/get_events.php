<?php
declare(strict_types=1);

require_once 'db.php'; // Assumes $conn is a valid, active mysqli instance
header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Structured Query Matching your Class Schema Properties
    // Columns are explicitly selected and aliased to camelCase for your JavaScript feed
    $query = "SELECT 
                id, 
                title, 
                description, 
                category, 
                start_date AS startDate, 
                end_date AS endDate, 
                capacity, 
                location 
              FROM events 
              ORDER BY start_date ASC";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Query execution failed: " . $conn->error);
    }

    // 2. Loop and Collect Row Data Objects
    $allEvents = [];
    while ($row = $result->fetch_assoc()) {
        // Enforce strong parameter typing before encoding
        $row['id']       = (int)$row['id'];
        $row['capacity'] = $row['capacity'] !== null ? (int)$row['capacity'] : null;
        $allEvents[]     = $row;
    }

    // 3. Return Clean JSON List Payload
    http_response_code(200);
    echo json_encode($allEvents);

    $result->free();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Internal workspace event transmission failure."
    ]);
}