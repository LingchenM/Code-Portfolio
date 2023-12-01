<?php
    session_start();

    if (!isset($_SESSION['username'])) {
        echo json_encode(['error' => 'User not logged in']);
        exit;
    }

    require 'database.php';

    $id = $_SESSION['id'];

    $stmt = $mysqli->prepare("SELECT id, user_id, event_title, event_date, event_time, category FROM events WHERE user_id=?");
    if (!$stmt) {
        printf("Query Prep Failed: %s\n", $mysqli->error);
        exit;
    }

    $stmt->bind_param('s', $id);
    $stmt->execute();
    $stmt->bind_result($eventID, $user_id, $event_title, $event_date, $event_time, $category);

    $events = [];
    while ($stmt->fetch()) {
        // Use htmlentities to escape user-generated content in the output
        $events[] = array(
            'event_id' => $eventID,
            'user_id' => $user_id,
            'event_title' => htmlentities($event_title),
            'event_date' => htmlentities($event_date),
            'event_time' => htmlentities($event_time),
            'category' => htmlentities($category)
        );
    }
    $stmt->close();
    echo json_encode($events);
?>
