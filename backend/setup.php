<?php
declare(strict_types=1);

require_once '../config/config.php'; // Assumes DB_HOST, DB_USER, DB_PASS, DB_NAME are available

// Enable strict MySQLi error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1. Initial Connection (Without selecting a database)
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    $conn->set_charset("utf8mb4");

    echo "Connection to server established successfully.\n";

    // 2. Initialize Clean Workspace Schema Environment
    $dbName = "`" . str_replace("`", "``", DB_NAME) . "`";
    $conn->query("CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db(DB_NAME);
    
    echo "Database schema verification complete: " . DB_NAME . "\n";

    // 3. Drop existing system tables in reverse order of dependencies
    echo "Cleaning up existing data structures...\n";
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("DROP TABLE IF EXISTS registrations");
    $conn->query("DROP TABLE IF EXISTS tickets");
    $conn->query("DROP TABLE IF EXISTS reviews");
    $conn->query("DROP TABLE IF EXISTS event_rates");
    $conn->query("DROP TABLE IF EXISTS user_bookmarks");
    $conn->query("DROP TABLE IF EXISTS events");
    $conn->query("DROP TABLE IF EXISTS venues");
    $conn->query("DROP TABLE IF EXISTS users");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    // 4. Create Table: USERS
    $conn->query("
        CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `first_name` VARCHAR(50) NOT NULL,
        `last_name` VARCHAR(50) NOT NULL,
        `username` VARCHAR(50) NOT NULL,
        `email` VARCHAR(100) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('participant', 'organizer') NOT NULL DEFAULT 'participant',
        `creation_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        -- Prevent multiple entries from consuming identical identity metrics
        UNIQUE KEY `unique_username` (`username`),
        UNIQUE KEY `unique_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    ");

    
    echo "-> Table 'users' generated.\n";

    // // 5. Create Table: VENUES (Normalized out from Event's protected array structure)
    // $conn->query("
    //     CREATE TABLE venues (
    //         id INT AUTO_INCREMENT PRIMARY KEY,
    //         name VARCHAR(100) NOT NULL,
    //         address VARCHAR(255) NOT NULL,
    //         capacity INT NOT NULL,
    //         is_available TINYINT(1) NOT NULL DEFAULT 1
    //     ) ENGINE=InnoDB
    // ");
    // echo "-> Table 'venues' generated.\n";

    // 6. Create Table: EVENTS
    $conn->query("
        CREATE TABLE events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            category ENUM('Tech', 'Music', 'Business', 'Education') NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            organizer_id INT NOT NULL,
            capacity INT NOT NULL,
            venue VARCHAR(150) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
        ) ENGINE=InnoDB
    ");
    echo "-> Table 'events' generated.\n";

    // 7. Create Table: REGISTRATIONS (N-N Association Class mapping)
    $conn->query("
        CREATE TABLE registrations (
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            status ENUM('confirmed', 'cancelled', 'waiting list') NOT NULL DEFAULT 'waiting list',
            ticket_type ENUM('free', 'VIP', 'paid') NOT NULL DEFAULT 'free',
            registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, event_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "-> Table 'registrations' association layout bound.\n";

    // 8. Create Table: TICKETS
    $conn->query("
        CREATE TABLE tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            type VARCHAR(50) NOT NULL DEFAULT 'free',
            event_id INT NOT NULL,
            owner_id INT NOT NULL,
            is_valid TINYINT(1) NOT NULL DEFAULT 1,
            issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "-> Table 'tickets' generated.\n";

    // 9. Create Table: REVIEWS (Decoupled array mapping from Event class)
    $conn->query("
        CREATE TABLE reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            feedback TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "-> Table 'reviews' sub-feed generated.\n";

    // 10. Create Table: EVENT RATES (Decoupled rates tracking list)
    $conn->query("
        CREATE TABLE event_rates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "-> Table 'event_rates' numerical sequence tracker generated.\n";

    // 11. Create Table: USER BOOKMARKS (Favorite array mapping from User domain class)
    $conn->query("
        CREATE TABLE user_bookmarks (
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            bookmarked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, event_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "-> Table 'user_bookmarks' generated.\n";

    echo "\nEnvironment setup complete. All data layers successfully provisioned.\n";

    $conn->close();

} catch (mysqli_sql_exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    die("\nCRITICAL INSTALLATION TERMINATION: " . $e->getMessage() . "\n");
}

?>