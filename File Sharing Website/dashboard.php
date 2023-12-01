<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Simple File Share System</title>
        <link rel="stylesheet" type="text/css" href="dashboard.css">
    </head>
    <body>
        <?php
            session_start();

            // Check if user is logged in
            if (!isset($_SESSION['username'])) {
            header('Location: login.html');
            exit();
            }

            // Scan files associated with the user
            $username = $_SESSION['username'];
            $userDir = "/srv/module2group/{$username}/";
            $file_list = scandir($userDir);

        ?>
        <h1 >Welcome, <?php echo $username; ?></h1><br><br>
        <h2>Your Files:</h2>
        <ol>
            <?php
                //list files using order list
                foreach ($file_list as $file) {
                    if ($file != '.' && $file != '..') {
                        $filename_output = htmlentities($file);
                        $file_view = "<li>$filename_output</li>";
                        echo $file_view;
                    }
                } 
            ?>
        </ol>
        <br>
        <br>

        <!--View file-->
        <h2>View</h2>
        <p>
            <form action="view.php" method="POST">
                Please select the file you want to view: &nbsp;&nbsp;
                <select name="filename">
                    <?php
                        //list file as select option
                        foreach ($file_list as $file) {
                            if ($file != '.' && $file != '..') {
                                $filename_output = htmlentities($file);
                                $file_view_btn = "<option value=$filename_output>$filename_output</option>";
                                echo $file_view_btn;
                            }
                        } 
                    ?>
                </select>
                <input type="submit" name="view" value="view">
            </form>
        </p>
        <br>
        <br>

        <!--Upload file-->
        <h2>Upload</h2>
        <p>
            <!--example from wiki: https://classes.engineering.wustl.edu/cse330/index.php?title=PHP#PHP_Language_Components-->
            <form enctype="multipart/form-data" action="upload.php" method="POST">
                <p>
                    <input type="hidden" name="MAX_FILE_SIZE" value="20000000">
                    <label for="uploadfile_input">Choose a file to upload:</label> <input name="uploadedfile" type="file" id="uploadfile_input">
                </p>
                <p>
                    <input type="submit" value="Upload File">
                </p>
            </form>
        </p>
        <br>
        <br>

        <!--Delete file-->
        <h2>Delete</h2>
        <p>
            <form action="delete.php" method="POST">
                Please select the file you want to delete: &nbsp;&nbsp;
                <select name="filename">
                    <?php
                        //list files as select option
                        foreach ($file_list as $file) {
                            if ($file != '.' && $file != '..') {
                                $filename_output = htmlentities($file);
                                $file_view_btn = "<option value=$filename_output>$filename_output</option>";
                                echo $file_view_btn;
                            }
                        } 
                    ?>
                </select>
                <input type="submit" name="delete" value="Delete">
            </form>
        </p>
        <br>
        <br>

        <!--Share file-->
        <h2>Share</h2>
        <p>
            <form action="share.php" method="POST">
                Please select the file you want to share: &nbsp;&nbsp;
                <select name="filename">
                    <?php
                        //list files as select option
                        foreach ($file_list as $file) {
                            if ($file != '.' && $file != '..') {
                                $filename_output = htmlentities($file);
                                $file_view_btn = "<option value=$filename_output>$filename_output</option>";
                                echo $file_view_btn;
                            }
                        } 
                    ?>
                </select>
                <!--Input the user that will share with-->
                Please input the user you want to share the file with: <input type="text" name="user_share">
                <input type="submit" name="share" value="Share">
            </form>
        </p>
        <br>
        <br>

        <!--Log Out-->
        <form action="logout.php" method="POST">
            <input type="submit" name="logout" value="Log Out">
        </form>
    </body>
</html>


