<?php

    // Association class between Event class and User class due to N-N group-by association
    class Registration{
        public int $userId; 
        public int $eventId;
        public string $status; //confirmed, cancelled, waiting list 
        public array $waitingList; //stores user when an event is full

        public function _construct($userId, $eventId, $status, $waitingList){
            $this->userId = $userId; 
            $this->eventId = $eventId;
            $this->status = $status; 
            $this->waitingList = $waitingList;
        }

        public function addToWaitingList($userId): void{
            
        }


        public function _destruct(){
            echo "Registration destroyed!";
            return; 
        }

    }



?>