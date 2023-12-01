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
        <br>
        <div>
            <h3>Your Stories:</h3>
        </div>
            <?php
                session_start();
                $user_id = htmlentities($_SESSION['id']);
                $token = htmlentities($_SESSION['token']);
                //create news btn
                echo "<form action='create_news.php' method='post'>
                        <input type='submit' name='create_news' value='create news'>
                        </form>";

                require "database.php";
                //show all news
                $stmt = $mysqli->prepare("select news.id, news.title, news.news_content, news.date, links.link 
                                        from (news join users on (news.author_id = users.id)) join links on (news.id = links.news_id) 
                                        where users.id=$user_id");
                if(!$stmt){
                    printf("Query Prep Failed: %s\n", $mysqli->error);
                    exit;
                }
                $stmt->execute();
                $stmt->bind_result($news_id, $news_title, $news_content, $date, $link);
                //edit btn and delete btn
                echo "<div id='contents'>";
                while($stmt->fetch()){
                    echo "<table>";
                    printf("<tr><th>%s.  %s</th></tr>", htmlspecialchars($news_id), $news_title);
                    printf("<tr><td>%s</td></tr>", htmlspecialchars($news_content));
                    printf("<tr><td>%s</td></tr>", htmlspecialchars($link));
                    echo "</table>";
                    echo "<form action = 'edit_news.php' method = 'post'>
                            <input type='submit' name='edit_news' value='edit'>
                            <input type='hidden' name='news_id' value='$news_id'>
                            </form>";
                    echo "<form action = 'delete_news.php' method = 'post'>
                            <input type='submit' name='delete_news' value='delete'>
                            <input type='hidden' name='token' value='$token'>
                            <input type='hidden' name='news_id' value='$news_id'>
                            </form>";
                    echo "<br>";
                }
                echo "</div>";
                $stmt->close();
            ?>
    </body>
</html>