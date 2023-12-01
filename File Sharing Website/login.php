<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Simple File Share System</title>
        <link rel="stylesheet" type="text/css" href="login.css">
        <style>
            #welcome{
                text-align: center;
                color: blueviolet;
            }
            body{
                background-color: antiquewhite;
            }
            div{
                text-align: center;
                margin: auto;
                margin-top: 10%;
            }
        </style>
    </head>
    <body>
        <h1 id="welcome">Welcome to Simple File Share System</h1>
        <div id="userInput">
            <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="POST">
                Username: <input type="text" name="username"><br><br>
                <input type="submit" name="btn_login" value="Login"> <input type="submit" name="btn_signup" value="SignUp">
            </form>
        </div>
            <?php
                //login
                if (isset($_POST['username']) && isset($_POST['btn_login'])){
                    session_start();
                    // Read usernames from users.txt
                    $user_list_file = fopen("/srv/module2group/users.txt", "r");
                    $username = htmlentities($_POST['username']);
    
                    //check input
                    if (empty($username) || !preg_match('/^[\w_\-]+$/', $username)){
                        // Invalid input
                        echo "<br><div>Invalid username</div>";
                        exit;
                    }
                    $_SESSION['username'] = $username;
                    //find username in users.txt
                    while(!feof($user_list_file)){
                        $username_inList = trim(fgets($user_list_file));
                        if ($username == $username_inList){
                            // User is valid
                            $_SESSION['username'] = htmlentities($username);
                            header('Location: dashboard.php');
                            exit;
                        }
                    }
    
                    //username not found
                    echo "<br><div>Wrong username</div>";
                    fclose($h);

                }

                //sign up
                if (isset($_POST['username']) && isset($_POST['btn_signup'])){
                    session_start();
                    $username = htmlentities($_POST['username']);
                    if (empty($username) || !preg_match('/^[\w_\-]+$/', $username)){
                        // Invalid input, redirect to login page
                        echo "<br><div>Invalid username</div>";
                        exit;
                    }

                    $user_list_file = fopen("/srv/module2group/users.txt", "r");
                    while(!feof($user_list_file)){
                        $username_inList = trim(fgets($user_list_file));
                        if ($username == $username_inList){
                            // User already exist
                            echo "<br><div>This user already exists</div>";
                            exit;
                        }
                    }
                    
                    //write the username to users.txt
                    $userlist = fopen("/srv/module2group/users.txt", "a");
                    fwrite($userlist, "\n".$username);
                    fclose($userlist);
                    //make folder for new user
                    mkdir("/srv/module2group/".$username);
                    echo "<br><div>Sign Up Successfully</div>";
                }
            ?>
    </body>
</html>