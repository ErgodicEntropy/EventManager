<?php

// Represents any platform user (attendee/participant, organizer, admin). Stores identity info, roles, and registered events.

class User { 
    // State management properties
    public ?int $id = null; // Nullable until pulled from or saved to DB
    public string $firstName;
    public string $lastName;
    public string $username;
    public string $email;
    private string $password;
    public string $role; // participant, organizer, or admin
    private ?string $creationDate = null;

    // Database connection holder
    private mysqli $db;

    // The constructor now expects the database connection, matching: new User($conn)
    public function __construct(mysqli $conn) {
        $this->db = $conn;
    }

    /**
     * Executes an INSERT statement safely using prepared statements.
     */
    public function register(string $firstName, string $lastName, string $username, string $email, string $password, string $role): bool {
        // 1. Secure the plain-text password variant using standard bcrypt hashing
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // 2. Draft the safe SQL statement structure
        $sql = "INSERT INTO users (first_name, last_name, username, email, password, role) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        // 3. Prepare the statement to block SQL Injection payloads entirely
        if ($stmt = $this->db->prepare($sql)) {
            // Bind parameters ('ssssss' tells MySQL all six variables are strings)
            $stmt->bind_param("ssssss", $firstName, $lastName, $username, $email, $hashedPassword, $role);
            
            // Execute the operation against the persistent storage layer
            if ($stmt->execute()) {
                // Populate this instance context properties on successful creation loop
                $this->id = $stmt->insert_id;
                $this->firstName = $firstName;
                $this->lastName = $lastName;
                $this->username = $username;
                $this->email = $email;
                $this->role = $role;
                
                $stmt->close();
                return true;
            }
            $stmt->close();
        }
        return false;
    }

    // Accessor Methods (Getters)
    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }

    public function login() { /* Authentication module logic rules go here */ }
    public function logout() { /* Session clearance handlers go here */ }
    public function modifyProfile() { /* Target attribute update operations go here */ }
}

class Participant extends User {
    public ?int $id;
    public Ticket $ticket; // 
    public string $firstName;
    public string $lastName;
    public array $registeredEvents = [];
    public array $favoriteEvents = []; 

    public function registerForEvent(Event $event): void {
        if (!in_array($event, $this->registeredEvents, true)) {
            $this->registeredEvents[] = $event;
            $event->registerParticipant($this);
        }
    }

    public function cancelEvent(Event $event): void {
        if (in_array($event, $this->registeredEvents)){
            $this->registeredEvents = array_filter(
                $this->registeredEvents,
                fn($e) => $e->id !== $event->id
            );
    
            $event->cancelRegistration($this);
            return; 
        }
        return; 
    }

    public function getUpcomingEvents(): array {
        return $this->registeredEvents;
    }

    public function reviewEvent(Event $event, string $feedback): void{ // Participant feedback after attending an event (rating + comment).
        $event->addReview($feedback);
    }


    public function rateEvent(Event $event, int $rate): void{ // Numeric score associated with an event or organizer.
        $event->addRate($rate); 
    }

    public function bookmarkEvent(Event $event): void { // Allows Participants to save or bookmark liked or favorite events.
        $this->favoriteEvents[] = $event;
    }

    public function _destruct(){
        echo "Participant destroyed!";
        return; 
    }

}

?>