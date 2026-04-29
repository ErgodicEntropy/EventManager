<?php
// STEP 1: Connect WITHOUT selecting DB
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to MySQL<br>";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// STEP 2: Create Database
$conn->exec("CREATE DATABASE IF NOT EXISTS event_management");
echo "Database created or already exists<br>";

// STEP 3: Select DB
$conn->exec("USE event_management");

// STEP 4: Create Tables

// USERS
$conn->exec("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'organizer', 'participant') DEFAULT 'participant'
)
");

echo "Users table ready<br>";

// EVENTS
$conn->exec("
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    date DATETIME,
    location VARCHAR(255),
    organizer_id INT,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE
)
");

echo "Events table ready<br>";

// REGISTRATIONS
$conn->exec("
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    event_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, event_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
)
");

echo "Registrations table ready<br>";

echo "<br><b>Setup completed successfully 🎉</b>";
?>