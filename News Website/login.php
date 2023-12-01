<?php
    //login
    if (isset($_POST['username']) && isset($_POST['password'])){
        session_start();
        $username = htmlentities($_POST['username']);
        $password = htmlentities($_POST['password']);

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
            $_SESSION['id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
            
            header("Location: main_member.php");
        }
        else{
            echo "<br><div style='text-align: center;'>Username or password incorrect. Please try again.</div>";
        }
        $stmt->close();
    }
?>