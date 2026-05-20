<?php

// Represents any platform user (attendee/participant, organizer, admin). Stores identity info, roles, and registered events.
class User { 
    public int $id;
    public string $name;
    protected string $email;
    private string $password;
    public string $role; // participant, organizer or admin
    private DateTime $creationDate;

    public function _construct(int $id, string $name, string $email, string $password, string $role){
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role; 
        $this->creationDate = date();  
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }

    public function login(){

    }

    public function logout(){

    }

    public function modifyProfile(){

    }

}


class Participant extends User {
    public int $id;
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