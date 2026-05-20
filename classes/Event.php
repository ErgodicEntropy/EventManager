<?php

require "./Registration.php"; 

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

    public array $registeredUsers = []; //merely registered
    public array $presentUsers = []; //actually attended
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

    public function checkInUser(Participant $user): void { //convert user from registered to present
        foreach ($this->registeredUsers as $registeredUser) {
            if ($registeredUser->id === $user->id) {

                // avoid duplicates
                foreach ($this->presentUsers as $presentUser) {
                    if ($presentUser->id === $user->id) {
                        return;
                    }
                }

                $this->presentUsers[] = $user;
                $registration = new Registration($user->id, $this->id); 
                $registration->status = "confirmed"; 
                return;
            }
        }
    }

    public function getRevenue(): float {
        $total = 0;

        foreach ($this->tickets as $ticket) {
            if ($ticket->type == "paid"){
                $total += $ticket->price;
            }
        }

        return $total;
    }

    public function getAttendanceRate(): float {
        $registered = count($this->registeredUsers);

        if ($registered === 0) {
            return 0;
        }

        return (count($this->presentUsers) / $registered) * 100;
    }

    public function setVenue(string $name, string $address, int $capacity, bool $isAvailable): void {
        $arr = Array("name"=>$name, "address"=>$address, "capacity"=>$capacity, "isAvailable"=>$isAvailable); 
        $this->venue = $arr;
    }

    public function isFull(): bool {
        return count($this->registeredUsers) >= $this->capacity;
    }

    public function getRemainingSeats(): int {
        return $this->capacity - count($this->registeredUsers);
    }

    public function registerUser(Participant $user, string $ticketType): void {
        if ($this->isFull()) {
            return;
        }

        foreach ($this->registeredUsers as $registeredUser) {
            if ($registeredUser->id === $user->id) {
                return;
            }
        }

        $registration = new Registration($user->id, $this->id); 
        $registration->status = "waiting list"; 
        $registration->ticketType = $ticketType; //free, paid, VIP

        $this->registeredUsers[] = $user;
    }

    public function cancelRegistration(Participant $user): void {
        $this->registeredUsers = array_filter(
            $this->registeredUsers,
            fn($u) => $u->id !== $user->id
        );

        $registration = new Registration($user->id, $this->id); 
        $registration->status = "cancelled"; 

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