<?php
    header("Content-Type: application/json");

    $json_str = file_get_contents('php://input');
    $json_obj = json_decode($json_str, true);

    $username = $json_obj['username'];
    $password = $json_obj['password'];
    //login

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

    require "database.php";
    //select id password
    $stmt = $mysqli->prepare("select id, password_hash from users where username=?");
    if(!$stmt){
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($user_id, $password_hash);
    $stmt->fetch();

    //verify password
    if (password_verify($password, $password_hash)){
        session_start();
        $params = session_get_cookie_params();
        session_set_cookie_params(
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            $params['secure'],
            true
        );
        $_SESSION['id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
        echo json_encode(array(
            "success" => true,
            'message' => $_SESSION['token']
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
    $stmt->close();
    
?>
