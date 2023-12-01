<!DOCTYPE html>
<html lang="en">
    <head>
        <title>WashU News Website</title>
        <link rel="stylesheet" type="text/css" href="main.css">
    </head>
    <body>
        <div id="title_login">
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
        <br>
        <?php
            session_start();
            //loading page
            if (isset($_POST['edit_news'])){
                $news_id = $_POST['news_id'];
                $_SESSION['news_id'] = $news_id;

                require "database.php";
                //get original news
                $stmt = $mysqli->prepare("select news.title, news.news_content, links.link from news join links on (news.id = links.news_id) 
                                        where news.id = ?;");
                if(!$stmt){
                    printf("Query Prep Failed: %s\n", $mysqli->error);
                    exit;
                }
                $stmt->bind_param('s', $news_id);
                $stmt->execute();
                $stmt->bind_result($title, $news_content, $link);
                $stmt->fetch();
                $stmt->close();
            }
        ?>
        <!--show original news-->
        <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
            title: <textarea rows='1' cols='100' name='title'><?php session_start(); echo htmlentities($title); ?></textarea><br>
            news: <textarea rows='8' cols='100' name='news_content'><?php session_start(); echo htmlentities($news_content); ?></textarea><br>
            link: <textarea rows='2' cols='100' name='link'><?php session_start(); echo htmlentities($link); ?></textarea><br>
            <input type='hidden' name='token' value='<?php session_start(); echo htmlentities($_SESSION['token']);?>'>
            <input type="submit" name="submit" value="save">
        </form>

        <?php
            //edit
            session_start();
            if (isset($_POST['submit'])){
                //check token
                if(!hash_equals($_POST['token'], $_SESSION['token'])){
                    die("Request forgery detected");
                }
                else{
                    //get new news
                    $new_title = $_POST['title'];
                    if (!preg_match('/^[\w_\-]+$/', $new_title)){
                        echo "<p>invalid input</p>";
                        exit();
                    }
                    $new_content = $_POST['news_content'];
                    if (!preg_match('/^[\w_\-]+$/', $new_content)){
                        echo "<p>invalid input</p>";
                        exit();
                    }
                    if (isset($link)){
                        $new_link = $_POST['link'];
                        if (!preg_match('/^[\w_\-]+$/', $new_link)){
                            echo "<p>invalid input</p>";
                            exit();
                        }
                    }
                    
                    $date = date("Y-m-d");
                    $news_id = $_SESSION['news_id'];

                    require "database.php";
                    //update news
                    $stmt = $mysqli->prepare("update news set title=?, news_content=?, date=? where id=?;");
                    if(!$stmt){
                        printf("Query Prep Failed: %s\n", $mysqli->error);
                        exit;
                    }
                    $stmt->bind_param('ssss', $new_title, $new_content, $date, $news_id);
                    $stmt->execute();
                    $stmt->close();
                    
                    if (isset($link)){
                        //update link
                        $stmt = $mysqli->prepare("update links set link=? where news_id=?;");
                        if(!$stmt){
                            printf("Query Prep Failed: %s\n", $mysqli->error);
                            exit;
                        }
                        $stmt->bind_param('ss', $new_link, $news_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    header("Location: manage.php");
                }
            }
            
        ?>
    </body>
</html>

