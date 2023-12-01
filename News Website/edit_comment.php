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
        <?php
            session_start();
            //check btn
            if (isset($_POST['edit_comment'])){
                $news_id = htmlentities($_POST['news_id']);
                $comment_id = htmlentities($_POST['comment_id']);
                $_SESSION['news_id'] = $news_id;

                require "database.php";
                //get original news
                $stmt = $mysqli->prepare("select news.id, news.title, news.news_content, news.date, users.username, links.link 
                                        from (news join users on (news.author_id = users.id)) join links on (news.id = links.news_id)
                                         where news.id = ?;");
                if(!$stmt){
                    printf("Query Prep Failed: %s\n", $mysqli->error);
                    exit;
                }
                $stmt->bind_param('s', $news_id);
                $stmt->execute();
                $stmt->bind_result($news_id, $title, $news_content, $date, $author_name, $link);
                $stmt->fetch();
                $stmt->close();

                //get comments
                $stmt = $mysqli->prepare("select comments_content from comments where id = ?");
                if(!$stmt){
                    printf("Query Prep Failed: %s\n", $mysqli->error);
                    exit;
                }
                $stmt->bind_param('s', $comment_id);
                $stmt->execute();
                $stmt->bind_result($comment);
                $stmt->fetch();
                $stmt->close();
            }
        ?>
        <!--show original news-->
        <table>
            <tr><th><?php session_start(); printf("%s.  %s", htmlspecialchars($news_id), htmlspecialchars($title));?></th></tr>
            <tr><td><?php session_start(); printf("%s", htmlspecialchars($news_content));?></td></tr>
            <tr><td><?php session_start(); printf("%s", htmlspecialchars($link));?></td></tr>
            <tr><td><?php session_start(); printf("written by: %s", $author_name);?></td></tr>
            <tr><td><?php session_start(); printf("post date: %s", htmlspecialchars($date));?></td></tr>
        </table>
        <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
            <p>comments: </p>
            <textarea rows='3' cols='100' name='new_comment'><?php session_start(); echo htmlentities($comment)?></textarea><br>
            <input type='hidden' name='token' value='<?php session_start(); echo htmlentities($_SESSION['token'])?>'>
            <input type="hidden" name="comment_id" value="<?php session_start(); echo htmlentities($comment_id) ?>">
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
                    //get new comment
                    $new_comment = $_POST['new_comment'];
                    if (!preg_match('/^[\w_\-]+$/', $new_comment)){
                        echo "<p>invalid input</p>";
                        exit();
                    }
                    $comment_id = htmlentities($_POST['comment_id']);
                    $date = date("Y-m-d");
                    require "database.php";
                    //update comment
                    $stmt = $mysqli->prepare("update comments set comments_content=?, date=? where id=?;");
                    if(!$stmt){
                        printf("Query Prep Failed: %s\n", $mysqli->error);
                        exit;
                    }
                    $stmt->bind_param('sss', $new_comment, $date, $comment_id);
                    $stmt->execute();
                    $stmt->close();
                    header("Location: main_member.php");
                }
            }
        ?>
    </body>
</html>

