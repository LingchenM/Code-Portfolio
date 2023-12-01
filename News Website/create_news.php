<!DOCTYPE html>
<html lang="en">
    <head>
        <title>WashU News Website</title>
        <link rel="stylesheet" type="text/css" href="main.css">
    </head>
    <body>
        <br>
        <div id="title_login">
            <!--title and account btn-->
            <p id="title"><strong>WashU News Website</strong></p>
            <p id="member">welcome: <?php 
                                        session_start(); 
                                        if (!isset($_SESSION['username'])){
                                            header("Location: login_signup.html");
                                            exit();
                                        }
                                        echo htmlentities($_SESSION['username']); 
                                    ?>
            </p>
            <a href="http://ec2-18-190-159-156.us-east-2.compute.amazonaws.com/~lingchen.m/m3_news/logout.php">
                <button id="logout"><strong>log out</strong></button>
            </a>
            <a href="http://ec2-18-190-159-156.us-east-2.compute.amazonaws.com/~lingchen.m/m3_news/main_member.php">
                <button id="home"><strong>home</strong></button>
            </a>
        </div>
        <!--show the input textarea-->
        <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
            title: <textarea rows="1" cols="100" name="title"></textarea><br>
            news: <textarea rows="8" cols="100" name="news_content"></textarea><br>
            link: <textarea rows="2" cols="100" name="link"></textarea><br>
            <input type='hidden' name='token' value='<?php session_start(); echo htmlentities($_SESSION['token']);?>'>
            <input type="submit" name="create" value="submit">
        </form>
        <br>
        <?php
            session_start();
            if (isset($_POST['create'])){
                // check token
                if(!hash_equals($_POST['token'], $_SESSION['token'])){
                    die("Request forgery detected");
                }
                else{
                    // check if fields are filled
                    if (isset($_POST['submit']) && (!isset($_POST['title']) || !isset($_POST['news_content']))){
                        echo "<p>Please fill title and content.</p>";
                        exit();
                    }

                    // get info
                    $user_id = $_SESSION['id'];
                    $username = $_SESSION['username'];
                    $title = $_POST['title'];
                    $news_content = $_POST['news_content'];
                    if (!preg_match('/^[\w_\-]+$/', $post) ){
                        echo "<p>invalid input</p>";
                        exit();
                    }
                    if (!preg_match('/^[\w_\-]+$/', $news_content)){
                        echo "<p>invalid input</p>";
                        exit();
                    }
                    if (isset($_POST['link'])){
                        $link = $_POST['link'];
                    }
                    $date = date("Y-m-d");

                    require "database.php";
                    
                    // insert news
                    $stmt = $mysqli->prepare("insert into news (title, news_content, author_id, date) values (?, ?, ?, ?);");
                    if(!$stmt){
                        printf("Query Prep Failed: %s\n", $mysqli->error);
                        exit;
                    }
                    $stmt->bind_param('ssss', $title, $news_content, $user_id, $date);
                    $stmt->execute();
                    $stmt->close();

                    // insert link if has link
                    if (isset($_POST['link'])){
                        // get news id
                        $stmt = $mysqli->prepare("select id from news where news_content=?;");
                        if(!$stmt){
                            printf("Query Prep Failed: %s\n", $mysqli->error);
                            exit;
                        }
                        $stmt->bind_param('s', $news_content);
                        $stmt->execute();
                        $stmt->bind_result($news_id);
                        $stmt->fetch();
                        $stmt->close();
                        
                        //insert
                        $stmt = $mysqli->prepare("insert into links (link, news_id) values (?, ?);");
                        if(!$stmt){
                            printf("Query Prep Failed: %s\n", $mysqli->error);
                            exit;
                        }
                        $stmt->bind_param('ss', $link, $news_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                    header("Location: manage.php");
                }
            }
        ?>
    </body>
</html>

