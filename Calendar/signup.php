<?php
    header("Content-Type: application/json");

    $json_str = file_get_contents('php://input');
    $json_obj = json_decode($json_str, true);

    $username = $json_obj['username'];
    $password = $json_obj['password'];
    $com_pass = $json_obj['confirm_pass'];

    //sign up
    if (isset($username) && isset($password) && isset($com_pass)){

        if (empty($username) || !preg_match('/^[\w_\-]+$/', $username)){
            // Invalid input, redirect to login page
            echo json_encode(['status' => 'error', 'message' => 'Invalid Input']);
            exit;
        }
        
        if (empty($password) || !preg_match('/^[\w_\-]+$/', $password)){
            // Invalid input, redirect to login page
            echo json_encode(['status' => 'error', 'message' => 'Invalid Input']);
            exit;
        }
        
        if ($password != $com_pass){
            // Wrong password
            echo json_encode(['status' => 'error', 'message' => 'Passwords are different, please input again']);
            exit;
        }

        require "database.php";
        // hash password
        $password = password_hash($password, PASSWORD_DEFAULT);
        // insert into users
        $stmt = $mysqli->prepare("insert into users (username, password_hash) values (?, ?)");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        
        $stmt->bind_param('ss', $username, $password);
        $stmt->execute();
        $stmt->close();
        echo json_encode(array(
            "success" => true
        ));
        exit;
    }
    else {
        echo json_encode(array(
            "success" => false,
            "message" => "Incorrect Username or Password"
        ));
        exit;
    }
?>