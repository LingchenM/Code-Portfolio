<?php
    session_start();

    if (!isset($_SESSION['id'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    require 'database.php';

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    if (
        !isset($data['event_title']) ||
        !isset($data['event_date']) ||
        !isset($data['event_time'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Incomplete data']);
        exit;
    }

    //get and set variables
    $invitee_success = 0;
    $inviter_success = 0;
    $event_title = htmlentities($data['event_title']);
    $event_date = htmlentities($data['event_date']);
    $event_time = htmlentities($data['event_time']);
    $category = htmlentities($data['category']);
    $user_list = $data['event_user'];

    $token = $data['token'];
    if (!hash_equals($token, $_SESSION['token'])) {
        echo json_encode(['success' => false, 'message' => 'Request forgery detected']);
        exit;
    }
    //add events for invitesss
    if ($user_list[0] != ""){
        foreach ($user_list as $user){
            $stmt = $mysqli->prepare("select id from users where username=?");
            if (!$stmt) {
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $stmt->bind_result($user_id);
            $stmt->fetch();
            $stmt->close();
    
            $stmt = $mysqli->prepare("INSERT INTO events(user_id, event_title, event_date, event_time, category) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
    
            $stmt->bind_param('issss', $user_id, $event_title, $event_date, $event_time, $category);
            if ($stmt->execute()) {
                $invitee_success = 1;
            } else {
                $invitee_success = 0;
            }
        
            $stmt->close();
        }
    }
    else{
        $invitee_success = 1;
    }
    //add event for inviter
    $stmt = $mysqli->prepare("INSERT INTO events(user_id, event_title, event_date, event_time, category) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }

    $stmt->bind_param('sssss', $_SESSION['id'], $event_title, $event_date, $event_time, $category);
    if ($stmt->execute()) {
        $inviter_success = 1;
    } else {
        $inviter_success = 0;
    }

    if ($invitee_success && $inviter_success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database insertion failed']);
    }
    $stmt->close();
?>
