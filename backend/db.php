<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// Enable strict MySQLi error reporting (mimics PDO::ERRMODE_EXCEPTION)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Initialize MySQLi connection using your existing config constants
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Establish the workspace character set to UTF-8
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // Set response header to JSON to match your SaaS endpoints
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);

    // Log the actual error internally for debugging, but output a clean message to the client
    error_log("Database Connection Failure: " . $e->getMessage());

    echo json_encode([
        "status" => "error",
        "message" => "Database connection failure. Please contact your workspace administrator."
    ]);
    exit;
}