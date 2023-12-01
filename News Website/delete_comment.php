<?php
    session_start();
    if (!isset($_SESSION['username'])){
        header("Location: login_signup.html");
        exit();
    }
    //btn pressed
    if (isset($_POST['delete_comment'])){
        //check token
        if(!hash_equals($_POST['token'], $_SESSION['token'])){
            die("Request forgery detected");
        }
        else{
            $comment_id = $_POST['comment_id'];

            require "database.php";
            //delete comments
            $stmt = $mysqli->prepare("delete from comments where id = ?;");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('s', $comment_id);
            $stmt->execute();
            $stmt->close();

            header("Location: main_member.php");
        }
    }
?>
