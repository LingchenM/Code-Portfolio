<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Simple File Share System</title>
        <link rel="stylesheet" type="text/css" href="upload_success.css">
    </head>
    <body>
        <p id="notice"><strong>File Shared Successfully</strong></p>
        <div>
            <p id="redirect">Redirect to Dashboard in 3 seconds</p>
        </div>
            <?php
                header('refresh: 3; url=http://ec2-18-190-159-156.us-east-2.compute.amazonaws.com/~lingchen.m/m2_fileshare/dashboard.php');
            ?>
    </body>
</html>