<?php
    session_start();

    if (isset($_SESSION['id'])) {
        $response = ['status' => 'logged_in', 'username' => htmlentities($_SESSION['username'])];
    } else {
        $response = ['status' => 'not_logged_in'];
    }

    echo json_encode($response);
    ?>
