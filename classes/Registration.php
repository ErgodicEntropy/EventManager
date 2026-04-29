<?php
class Registration {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($user_id, $event_id) {
        $stmt = $this->conn->prepare(
            "INSERT INTO registrations (user_id, event_id) VALUES (?, ?)"
        );
        return $stmt->execute([$user_id, $event_id]);
    }
}
?>