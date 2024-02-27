<?php

	require_once("func/func_user.php");
	
    // check if a file was submitted
    if(!isset($_FILES['userfile'])) {
        echo '<p>Please select a file</p>';
    }
    else
        {
        try {
            uploadAvatar();
            // give praise and thanks to the php gods
            echo '<p>Thank you for submitting</p>';
        }
        catch(Exception $e) {
            echo $e->getMessage();
            echo 'Sorry, could not upload file';
        }
    }
?>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="MAX_FILE_SIZE" value="20000000" />
	<input name="userfile[]" type="file" />
	<input type="submit" value="Submit" />
</form>