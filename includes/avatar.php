<?php
	session_start();

	require_once("config.php");

    // just so we know it is broken
    error_reporting(E_ALL);
    // some basic sanity checks
    if(true) {        //connect to the db
       
        // get the image from the db
        $sql = "SELECT avatar FROM userdb WHERE member_id=".$_GET['MID'];
 
        // the result of the query
        $result = mysql_query("$sql") or die("Invalid query: " . mysql_error());
 
        // set the header for the image
        header("Content-type: image/jpeg");
        echo mysql_result($result, 0);
 
    }
    else {
        echo 'No avatar';
    }
    
    session_write_close();
?>