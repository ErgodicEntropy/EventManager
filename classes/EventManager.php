<?php

// Handles business logic such as creating events, registering users, searching events, and enforcing rules.


// Registration
// Links a User to an Event, tracking status (confirmed, cancelled, waiting list).
// WaitingList
// Stores users when an event is full.
// Attendance
// Tracks actual participation (especially for online check-ins).
// CheckInService
// Handles marking attendance when a user joins an online session.

class EventManager {
    private array $events = [];
    private array $users = [];

    public function createEvent(Event $event): void {
        $this->events[] = $event;
    }

    public function deleteEvent(int $eventId): void {
        $this->events = array_filter(
            $this->events,
            fn($e) => $e->id !== $eventId
        );
    }

    public function getEvent(int $id): ?Event {
        foreach ($this->events as $event) {
            if ($event->id === $id) {
                return $event;
            }
        }
        return null;
    }

    public function searchEvents(string $keyword): array {
        return array_filter($this->events, function ($event) use ($keyword) {
            return str_contains(strtolower($event->title), strtolower($keyword));
        });
    }

    public function registerUserToEvent(int $userId, int $eventId): void {
        $user = $this->getUser($userId);
        $event = $this->getEvent($eventId);

        if ($user && $event) {
            $user->registerForEvent($event);
        }
    }

    public function cancelUserFromEvent(int $userId, int $eventId): void {
        $user = $this->getUser($userId);
        $event = $this->getEvent($eventId);

        if ($user && $event) {
            $user->cancelEvent($event);
        }
    }

    public function addUser(User $user): void {
        $this->users[] = $user;
    }

    public function addVenue(Venue $venue): void {
        $this->venues[] = $venue;
    }

    public function getUser(int $id): ?User {
        foreach ($this->users as $user) {
            if ($user->id === $id) return $user;
        }
        return null;
    }

    public function getAllEvents(): array {
        return $this->events;
    }

    public function _destruct(){
        echo "EventManager destroyed!";
        return; 
    }

}
?>