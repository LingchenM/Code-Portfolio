<?php
    session_start();
    if (!isset($_SESSION['username'])){
        header("Location: login_signup.html");
        exit();
    }
    //btn pressed
    if (isset($_POST['delete_news'])){
        //check token
        if(!hash_equals($_POST['token'], $_SESSION['token'])){
            die("Request forgery detected");
        }
        else{
            $news_id = $_POST['news_id'];

            require "database.php";
            //delete comments
            $stmt = $mysqli->prepare("delete from comments where news_id = ?;");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('s', $news_id);
            $stmt->execute();
            $stmt->close();
            //delete link
            $stmt = $mysqli->prepare("delete from links where news_id = ?;");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('s', $news_id);
            $stmt->execute();
            $stmt->close();
            //delete news
            $stmt = $mysqli->prepare("delete from news where id = ?;");
            if(!$stmt){
                printf("Query Prep Failed: %s\n", $mysqli->error);
                exit;
            }
            $stmt->bind_param('s', $news_id);
            $stmt->execute();
            $stmt->close();

            header("Location: manage.php");
        }
    }
?>
