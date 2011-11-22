<?php 

//Start session
session_start();

require_once('../func/func_start.php');
require_once('../func/func_end.php');
require_once('../func/func_user.php');
require_once('../func/func_fetch.php');
require_once('../func/func_misc.php');
require_once('../func/func_session.php');
require_once('../func/func_weeksdb.php');
require_once('../includes/config.php');

$action = clean($_REQUEST["action"]);

$xml_output .= xmlIntro();
// Login always needed
require_once("../api/api_login.php");

if (isset($_SESSION['SESS_MEMBER_ID']))
{	
	switch ($action) {
		case "newperiod":
			require_once("../api/api_newperiod.php");
			break;
		case "currentperiod":
			require_once("../api/api_currentperiod.php");
			break;
		case "ongoingperiod":
			require_once("../api/api_ongoingperiod.php");
			break;
		case "endbreak":
			require_once("../api/api_endbreak.php");
			break;
		case "weekupdate":
			require_once("../api/api_update_week.php");
			break;
		case "allweeksupdate":
			require_once("../api/api_update_all_weeks.php");
			break;
		case "updateholiday":
			require_once("../api/api_holiday.php");
			break;
		default:
			break;
		}
}

$xml_output .= xmlOutro();

close_session();

header('Content-type: text/xml'); 

echo $xml_output;

session_write_close();
?>	