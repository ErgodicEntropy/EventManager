<?php

// Central entity representing an online or offline event, including title, schedule, capacity, and linked participants.
class Event {
    public int $id;
    public string $title;
    public string $description;
    public string $category; // Tech, Music, Business, Education
    protected DateTime $startDate;
    protected DateTime $endDate;
    public User $organizer;
    public int $capacity;

    public array $attendees = [];
    public array $tickets = [];

    // Represents where the event takes place. 
    // For online platforms, this may be a Zoom/Meet link, streaming room, or hosted virtual space.

    protected array $venue = []; //an associative array containing $name, $address, $capacity, $isAvailable;

    public array $reviews = []; 
    public array $rates = []; 

    public function _construct(
        int $id,
        string $title,
        string $description,
        string $category,
        DateTime $startDate,
        DateTime $endDate,
        User $organizer,
        int $capacity
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->category = $category;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->organizer = $organizer;
        $this->capacity = $capacity;
    }

    public function setVenue(string $name, string $address, int $capacity, bool $isAvailable): void {
        $arr = Array("name"=>$name, "address"=>$address, "capacity"=>$capacity, "isAvailable"=>$isAvailable); 
        $this->venue = $arr;
    }

    public function isFull(): bool {
        return count($this->attendees) >= $this->capacity;
    }

    public function getRemainingSeats(): int {
        return $this->capacity - count($this->attendees);
    }

    public function registerUser(User $user): void {
        if ($this->isFull()) {
            return;
        }

        foreach ($this->attendees as $attendee) {
            if ($attendee->id === $user->id) {
                return;
            }
        }

        $this->attendees[] = $user;
    }

    public function cancelRegistration(User $user): void {
        $this->attendees = array_filter(
            $this->attendees,
            fn($u) => $u->id !== $user->id
        );
    }

    public function addTicket(Ticket $ticket): void {
        $this->tickets[] = $ticket;
    }

    public function canHost(): bool {
        return $this->venue["isAvailable"] && $this->venue["capacity"] <= $this->capacity;
    }

    public function reserveVenue(): void {
        $this->venue["isAvailable"] = false;
    }

    public function releaseVenue(): void {
        $this->venue["isAvailable"] = true;
    }

    public function addReview(string $feedback): void{
        $this->reviews[] = $feedback; 
    }

    public function addRate(int $rate): void{
        $this->rates[] = $rate; 
    }

    public function getRate(): int{
        $val;
        foreach ($this->rates as $rate){
            $val+=$rate;
        }
        return $val/count($this->rates);
    }

    public function getReviews(): array{
        return $this->reviews;
    }

    public function _destruct(){
        echo "Event destroyed!";
        return; 
    }
}

?>