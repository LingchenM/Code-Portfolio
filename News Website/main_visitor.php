<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Simlpe News Website</title>
        <link rel="stylesheet" type="text/css" href="main.css">
    </head>
    <body>
        <div id="title_login">
            <p id="title"><strong>WashU News Website</strong></p>
            <p id="visitor">visitor</p>
            <a href="http://ec2-18-190-159-156.us-east-2.compute.amazonaws.com/~lingchen.m/m3_news/login_signup.html">
                <button id="login"><strong>log in</strong></button>
            </a>
            <a href="http://ec2-18-190-159-156.us-east-2.compute.amazonaws.com/~lingchen.m/m3_news/login_signup.html">
                <button id="signup"><strong>sign up</strong></button>
            </a>
        </div>
        <div id="content">
            <form action="<?php echo htmlentities($_SERVER['PHP_SELF']); ?>" method="post">
                search by: 
                <select name='option'>
                    <option value="id">news id</option>
                    <option value="title">news title</option>
                    <option value="news_content">news content</option>
                    <option value="link">news link</option>
                    <option value="author_name">news author</option>
                    <option value="date">news post date</option>
                </select>
                <textarea rows="1" cols="100" name="search_content" placeholder="if searching date, please input as <yyyy-mm-dd>"></textarea>
                <input type="submit" name="search" value="search">
                <input type="submit" name="reset" value="reset">
            </form>
            <?php
                session_start();
                if (isset($_POST['reset'])){
                    header("Location: main_visitor.php");
                }
                $token = $_SESSION['token'];
                require "database.php";
                if (!isset($_POST['search'])){
                    //get news_id array
                    $news_id_list = array();
                    $stmt = $mysqli->prepare("select id from news");
                    if(!$stmt){
                        printf("Query Prep Failed: %s\n", $mysqli->error);
                        exit;
                    }
                    $stmt->execute();
                    $stmt->bind_result($id_member);
                    while($stmt->fetch()){
                        $news_id_list[] = $id_member;
                    }
                    $stmt->close();
                    //loop thru array
                    for ($i = 0; $i < count($news_id_list); $i++){
                        $news_id = $news_id_list[$i];
                        //select the news with cooresponding id
                        $stmt = $mysqli->prepare("select news.id, news.title, news.news_content, news.date, users.username, links.link 
                                                from (news join users on (news.author_id = users.id)) join links on (news.id = links.news_id) where news.id = ?");
                        if(!$stmt){
                            printf("Query Prep Failed: %s\n", $mysqli->error);
                            exit;
                        }
                        $stmt->bind_param('s', $news_id);
                        $stmt->execute();
                        $stmt->bind_result($news_id, $news_title, $news_content, $date, $author_name, $link);
                        //output to table
                        while($stmt->fetch()){
                            echo "<table id='news_comments'>";
                            printf("<tr><th>%s.  %s</th></tr>", htmlspecialchars($news_id), $news_title);
                            printf("<tr><td>%s</td></tr>", htmlspecialchars($news_content));
                            if (!empty($link)){
                                printf("<tr><td>%s</td></tr>", htmlspecialchars($link));
                            }
                            printf("<tr><td>written by: %s</td></tr>", $author_name);
                            printf("<tr><td>post date: %s</td></tr>", $date);
                        }
                        $stmt->close();
                        //select cooresponding comments
                        echo "<tr><td></td></tr>
                            <tr><td>comments: </td></tr>";
                        $stmt = $mysqli->prepare("select comments.id, comments.comments_content, comments.date, users.username 
                                                from comments join users on (comments.author_id = users.id) where comments.news_id = ?");
                        if(!$stmt){
                            printf("Query Prep Failed: %s\n", $mysqli->error);
                            exit;
                        }
                        $stmt->bind_param('s', $news_id);
                        $stmt->execute();
                        $stmt->bind_result($comment_id, $comment, $comment_date, $comment_user);
                        //output to table
                        while($stmt->fetch()){
                            printf("<tr><td>%s: %s &nbsp;&nbsp;| on %s</td></tr>", $comment_user, $comment, $date);
                        }
                        $stmt->close();
                        echo "</table>";
                        echo "<hr />";
                        echo "<br><br>";
                    }
                }
                else{
                    $option = $_POST['option'];
                    $search_content = $_POST['search_content'];
                    if (!isset($search_content)){
                        header("Location: main_visitor.php");
                        exit();
                    }
                    if (!preg_match('/^[\w_\-]+$/', $search_content)){
                        echo "<p>invalid search content</p>";
                        exit();
                    }
                    switch ($option){
                        case "id":
                            $stmt = $mysqli->prepare("select id from news where id=$search_content");
                            break;
                        
                        case "title":
                            $stmt = $mysqli->prepare("select id from news where title like '%$search_content%'");
                            break;
                        
                        case "news_content":
                            $stmt = $mysqli->prepare("select id from news where news_content like '%$search_content%'");
                            break;

                        case "link":
                            $stmt = $mysqli->prepare("select news.id from news join links on (news.id = links.news_id) where links.link like '%$search_content%'");
                            break;

                        case "author_name":
                            $stmt = $mysqli->prepare("select news.id from news join users on (news.author_id = users.id) where users.username like '%$search_content%'");
                            break;

                        case "date":
                            $search_content = date("Y-m-d", strtotime($search_content));
                            $stmt = $mysqli->prepare("select id from news where convert(date, DATETIME) like '$search_content%'");
                            break;
                    }

                    if(!$stmt){
                        printf("Query Prep Failed: %s\n", $mysqli->error);
                        exit;
                    }
                    $stmt->bind_param('s', $search_content);
                    $stmt->execute();
                    $stmt->bind_result($id_member);
                    $news_id_list = array();
                    while($stmt->fetch()){
                        $news_id_list[] = $id_member;
                    }
                    $stmt->close();
                    $news_id_list = array_unique($news_id_list);
                    if (count($news_id_list) == 0){
                        echo "<p>no result found.</p>";
                    }
                    else{
                        echo "<p>result:</p>";
                        for ($i = 0; $i < count($news_id_list); $i++){
                            $news_id = $news_id_list[$i];
                            //select the news with cooresponding id
                            $stmt = $mysqli->prepare("select news.id, news.title, news.news_content, news.date, users.username, links.link 
                                                    from (news join users on (news.author_id = users.id)) join links on (news.id = links.news_id) where news.id = ?");
                            if(!$stmt){
                                printf("Query Prep Failed: %s\n", $mysqli->error);
                                exit;
                            }
                            $stmt->bind_param('s', $news_id);
                            $stmt->execute();
                            $stmt->bind_result($news_id, $news_title, $news_content, $date, $author_name, $link);
                            //output to table
                            while($stmt->fetch()){
                                echo "<table id='news_comments'>";
                                printf("<tr><th>%s.  %s</th></tr>", htmlspecialchars($news_id), $news_title);
                                printf("<tr><td>%s</td></tr>", htmlspecialchars($news_content));
                                printf("<tr><td>%s</td></tr>", htmlspecialchars($link));
                                printf("<tr><td>written by: %s</td></tr>", $author_name);
                                printf("<tr><td>post date: %s</td></tr>", $date);
                            }
                            $stmt->close();
                            //select cooresponding comments
                            echo "<tr><td></td></tr>
                                <tr><td>comments: </td></tr>";;
                            $stmt = $mysqli->prepare("select comments.id, comments.comments_content, comments.date, users.username 
                                                    from comments join users on (comments.author_id = users.id) where comments.news_id = ?");
                            if(!$stmt){
                                printf("Query Prep Failed: %s\n", $mysqli->error);
                                exit;
                            }
                            $stmt->bind_param('s', $news_id);
                            $stmt->execute();
                            $stmt->bind_result($comment_id, $comment, $comment_date, $comment_user);
                            //output to table
                            while($stmt->fetch()){
                                printf("<tr><td>%s: %s &nbsp;&nbsp;| on %s</td></tr>", $comment_user, $comment, $date);
                            }
                            $stmt->close();
                            echo "</table>";
                            echo "<hr />";
                            echo "<br><br>";
                        }
                    }
                }
            ?>
        </div>
    </body>
</html>