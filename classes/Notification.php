<?php

    // Notification service which handles sending notificaitons to users via email, SMS msgs, or in-app alerts.
    class Notifier{ 

        public $time; 
        public string $notification; //Represents messages sent to users (event reminders, confirmations, cancellations).
        public string $template; //specifies the format of the notification message (welcome email, ticket confirmation, etc.).


        public function _construct($time){
            $this->time = $time;
        }

        
        public function _destruct(){
            echo "Notification destroyed!";
            return;
        }
    }

?>