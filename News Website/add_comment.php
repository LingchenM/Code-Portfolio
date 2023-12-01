<!DOCTYPE html>
<html lang="en">
    <head>
        <title>WashU News Website</title>
        <link rel="stylesheet" type="text/css" href="main.css">
    </head>
    <body>
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
        <?php
            session_start();
            //check btn pressed
            if (isset($_POST['add_comment'])){
                $news_id = $_POST['news_id'];
                $_SESSION['news_id'] = $news_id;

                require "database.php";
                //get original news
                $stmt = $mysqli->prepare("select news.id, news.title, news.news_content, news.date, users.username, links.link 
                                        from (news join users on (news.author_id = users.id)) join links on (news.id = links.news_id) where news.id = ?");
                if(!$stmt){
                    printf("Query Prep Failed: %s\n", $mysqli->error);
                    exit;
                }
                $stmt->bind_param('s', $news_id);
                $stmt->execute();
                $stmt->bind_result($news_id, $news_title, $news_content, $date, $author_name, $link);
                $stmt->fetch();
                $stmt->close();
                //display news
                echo "<table>";
                printf("<tr><th>%s.  %s</th></tr>", htmlspecialchars($news_id), $news_title);
                printf("<tr><td>%s</td></tr>", htmlspecialchars($news_content));
                printf("<tr><td>%s</td></tr>", htmlspecialchars($link));
                printf("<tr><td>written by: %s</td></tr>", $author_name);
                printf("<tr><td>post date: %s</td></tr>", $date);
                echo "</table>";
            }
        ?>
        <br>
        <!--comment area to edit-->
        <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
            comments: <textarea rows="3" cols="100" name="comment"></textarea>
            <input type='hidden' name='token' value='<?php session_start(); echo htmlentities($_SESSION['token']);?>'>
            <input type="submit" name="create_comment" value="create_comment">
        </form>

        <?php
            session_start();

            //check btn pressed
            if (isset($_POST['create_comment'])){
                if(!hash_equals($_POST['token'], $_SESSION['token'])){
                    die("Request forgery detected");
                }
                else{
                    $comment = $_POST['comment'];
                    if (!preg_match('/^[\w_\-]+$/', $comment)){
                        echo "<p>invalid input</p>";
                        exit();
                    }
                    $news_id = $_SESSION['news_id'];
                    $user_id = $_SESSION['id'];
                    $username = $_SESSION['username'];
                    $date = date("Y-m-d");
                    require "database.php";

                    //insert into comments
                    $stmt = $mysqli->prepare("insert into comments (comments_content, news_id, author_id, date) values (?, ?, ?, ?);");
                    if(!$stmt){
                        printf("Query Prep Failed: %s\n", $mysqli->error);
                        exit;
                    }
                    $stmt->bind_param('ssss', $comment, $news_id, $user_id, $date);
                    $stmt->execute();
                    $stmt->close();
                    header("Location: main_member.php");
                }
            }
        ?>
    </body>
</html>
        