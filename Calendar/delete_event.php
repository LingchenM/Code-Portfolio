<?php
    session_start();

    if (!isset($_SESSION['username'])) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }

    require 'database.php';

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['event_id'])) {
        echo json_encode(['error' => 'Invalid or missing data']);
        exit;
    }

    $event_id = intval($data['event_id']);  // Ensure $event_id is an integer

    $token = $data['token'];
    if (!hash_equals($token, $_SESSION['token'])) {
        echo json_encode(['error' => 'Request forgery detected']);
        exit;
    }

    $stmt = $mysqli->prepare("DELETE FROM events WHERE id = ?");
    if (!$stmt) {
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }

    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
?>