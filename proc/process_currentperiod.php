<?php 
//Start session
session_start();

require_once('../auth.php');

require_once('../includes/config.php');
require_once('../func/func_misc.php');
require_once('../func/func_fetch.php');

// check if something has been change outside this session
if(isset($_SESSION["SESS_ACTIVE_PERIOD"]) && $user->activeperiod != $_SESSION["SESS_ACTIVE_PERIOD"]) updateSession($user);

$xml_output = xmlIntro();
require_once("../api/api_currentperiod.php");
$xml_output .= xmlOutro();


header('Content-type: text/xml'); 

echo $xml_output;
session_write_close();
?>