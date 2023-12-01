<?php

    session_start();
    $file = $_POST['filename'];
    $username = $_SESSION['username'];
    $dir = "/srv/module2group/";
    $full_dir = sprintf("%s%s/%s", $dir, $username, $file);
    //delete the file
    unlink($full_dir);
    header("Location: dashboard.php")
?>