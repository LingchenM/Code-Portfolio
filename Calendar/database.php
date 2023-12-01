<?php
	$mysqli = new mysqli('localhost', 'm5group_user', 'm5group_pass', 'calendar');

	if($mysqli->connect_errno) {
		printf("Connection Failed: %s\n", $mysqli->connect_error);
		exit;
	}
?>