<?php
    //sign up
    if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['confirm_pass'])){
        session_start();
        $username = htmlentities($_POST['username']);
        $password = htmlentities($_POST['password']);
        $confirm_pass = htmlentities($_POST['confirm_pass']);

        if (empty($username) || !preg_match('/^[\w_\-]+$/', $username)){
            // Invalid input, redirect to login page
            echo "<br><div style='text-align: center;'>Invalid username</div>";
            exit;
        }
        
        if (empty($password) || !preg_match('/^[\w_\-]+$/', $password)){
            // Invalid input, redirect to login page
            echo "<br><div style='text-align: center;'>Invalid password</div>";
            exit;
        }
        
        if ($password != $confirm_pass){
            // Wrong password
            echo "<br><div style='text-align: center;'>Passwords are different, please input again.</div>";
            exit;
        }

        require "database.php";
        // hash password
        $password=password_hash($password, PASSWORD_DEFAULT);
        // insert into users
        $stmt = $mysqli->prepare("insert into users (username, password_hash) values (?, ?)");
        if(!$stmt){
            printf("Query Prep Failed: %s\n", $mysqli->error);
            exit;
        }
        
        $stmt->bind_param('ss', $username, $password);
        $stmt->execute();
        $stmt->close();
        echo "<br><div style='text-align: center;'>Account created. You can now login.</div>";
        echo "<br><div style='text-align: center;'>Redirect to main page in 3 seconds.</div>";
        header('refresh: 3; url=http://ec2-18-190-159-156.us-east-2.compute.amazonaws.com/~lingchen.m/m3_news/main_visitor.php');
    }
?>