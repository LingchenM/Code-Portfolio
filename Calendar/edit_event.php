<?php
    session_start();

    if (!isset($_SESSION['username'])) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }

    require 'database.php';

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['event_id']) || !isset($data['event_title']) || !isset($data['event_time'])) {
        echo json_encode(['error' => 'Invalid or missing data']);
        exit;
    }

    $event_id = intval($data['event_id']);  // Ensure $event_id is an integer
    $event_title = htmlentities($data['event_title']); // Escape user input
    $event_time = htmlentities($data['event_time']); // Escape user input
    $category = htmlentities($data['category']);

    $token = $data['token'];
    if (!hash_equals($token, $_SESSION['token'])) {
        echo json_encode(['error' => 'Request forgery detected']);
        exit;
    }

    $stmt = $mysqli->prepare("UPDATE events SET event_title=?, event_time=?, category=? WHERE id=?");
    if (!$stmt) {
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }

    $stmt->bind_param('sssi', $event_title, $event_time, $category, $event_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
?>