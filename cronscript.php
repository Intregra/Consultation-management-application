<?php

require_once('functions.php');

// send notifications if there are any to send
$result = kon_db('SELECT * FROM kon_user');
while (($row = $result->fetch_assoc()) != null) {
	before_send_notification($row);
}