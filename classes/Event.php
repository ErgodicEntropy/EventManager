<?php
class Event {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($title, $desc, $date, $location, $organizer_id) {
        $stmt = $this->conn->prepare(
            "INSERT INTO events (title, description, date, location, organizer_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$title, $desc, $date, $location, $organizer_id]);
    }

    public function getAll() {
        $stmt = $this->conn->query("SELECT * FROM events ORDER BY date ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>