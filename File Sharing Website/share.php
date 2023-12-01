<?php
    session_start();
    //declare variables
    $filename = $_POST['filename'];
    $username = $_SESSION['username'];
    $usershare = $_POST['user_share'];
    $user_list_file = fopen("/srv/module2group/users.txt", "r");

    //check if the input user exists
    if (empty($usershare) || !preg_match('/^[\w_\-]+$/', $usershare)){
        // Invalid input
        echo "Invalid username";
    }

    $has_user = 0;
    while(!feof($user_list_file)){
        $username_inList = trim(fgets($user_list_file));
        if ($usershare == $username_inList){
            // User is valid
            $has_user = 1;
        }
    }

    //copy file from username to usershare
    if ($has_user){
        $dir = "/srv/module2group/";
        $full_path = sprintf("%s%s/%s", $dir, $username, $filename);
        $dest_full_path = sprintf("%s%s/%s", $dir, $usershare, $filename);
        copy($full_path, $dest_full_path);
        header("Location: share_success.php");
    }
    else{
        header("Location: share_failure.php");
        exit;
    }
?>