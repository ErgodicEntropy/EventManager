<?php

// Represents any platform user (attendee/participant, organizer, admin). Stores identity info, roles, and registered events.

class User {
    public int $id;
    public string $name;
    public string $type; // participant, organizer or admin (role)
    public string $email;
    public array $registeredEvents = [];

    public function __construct(int $id, string $name, string $email, bool $isOrganizer = false, string $type) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->type = $type; 
    }

    public function registerForEvent(Event $event): void {
        if (!in_array($event, $this->registeredEvents, true)) {
            $this->registeredEvents[] = $event;
            $event->registerUser($this);
        }
    }

    public function cancelEvent(Event $event): void {
        $this->registeredEvents = array_filter(
            $this->registeredEvents,
            fn($e) => $e->id !== $event->id
        );

        $event->cancelRegistration($this);
    }

    public function getUpcomingEvents(): array {
        return $this->registeredEvents;
    }

    public function _destruct(){
        echo "User destroyed!";
        return; 
    }

}


class Participant extends User{ 

}

class Organizer extends User{ 

}

class Admin extends User{ 

}



?>