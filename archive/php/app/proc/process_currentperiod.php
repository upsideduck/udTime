<?php 
//Start session
session_start();

require_once('../auth.php');

require_once('../includes/config.php');
require_once('../func/func_misc.php');
require_once('../func/func_fetch.php');

// check if something has been change outside this session
if($user->activeperiod != $_SESSION["SESS_ACTIVE_PERIOD"]) updateSession($user);

$output = new output();
require_once("../api/api_currentperiod.php");

header('Content-type: text/xml'); 
echo  $output->outputToXml();

session_write_close();
?>