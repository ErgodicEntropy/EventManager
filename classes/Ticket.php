<?php

// Represents access to an event. Defines type (free, VIP, paid), price, and ownership.

class Ticket { //replaces Payment class
    public int $id;
    public float $price;
    public string $type; //free, VIP, paid, etc.
    public Event $event;
    public Participant $owner;
    public bool $isValid = true;

    public function __construct(int $id, float $price, string $type, Event $event, Participant $owner) {
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