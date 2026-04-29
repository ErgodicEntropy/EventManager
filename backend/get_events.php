<?php
require 'db.php';
require_once '../classes/Event.php';

$event = new Event($conn);

echo json_encode($event->getAll());
?>