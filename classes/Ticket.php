<?php

// Represents access to an event. Defines type (free, VIP, paid), price, and ownership.

class Ticket {
    public int $id;
    public float $price;
    public string $type; //free, VIP, paid, etc.
    public Event $event;
    public User $owner;
    public bool $isValid = true;

    public function __construct(int $id, float $price, string $type, Event $event, User $owner) {
        $this->id = $id;
        $this->price = $price;
        $this->type = $type;
        $this->event = $event;
        $this->owner = $owner;
    }

    public function validate(): void {
        $this->isValid = true;
    }

    public function invalidate(): void {
        $this->isValid = false;
    }

    public function _destruct(){
        echo "Ticket destroyed!";
        return; 
    }

}
?>