<?php
    session_start();

    //get username and filename
    $filename = $_POST['filename'];
    $username = $_SESSION['username'];

    //set file dir
    $dir = '/srv/module2group/' . "$username/" . "$filename";

    //recommended command of getting mime type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($dir);

    //examples from wiki: https://classes.engineering.wustl.edu/cse330/index.php?title=PHP#PHP_Language_Components
    header("Content-Type: ".$mime);
    header('Content-Disposition: inline; filename="'.basename($dir).'"');
    readfile($dir);
?>