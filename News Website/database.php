<?php

$mysqli = new mysqli('localhost', 'm3group_user', 'm3group_pass', 'm3group');

if($mysqli->connect_errno) {
	printf("Connection Failed: %s\n", $mysqli->connect_error);
	exit;
}
?>