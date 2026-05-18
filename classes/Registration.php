<?php

    // Association class between Event class and User class due to N-N group-by association
    class Registration{
        public int $userId; 
        public int $eventId;
        public string $status; //confirmed, cancelled, waiting list 
        public string $ticketType; //free, VIP, paid

        public function _construct($userId, $eventId, $status, $ticketType){
            $this->userId = $userId; 
            $this->eventId = $eventId;
            $this->status = $status; 
            $this->ticketType = $ticketType; 
        }

        public function _destruct(){
            echo "Registration destroyed!";
            return; 
        }

    }



?>