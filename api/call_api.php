<?php 

//Start session
session_start();

require_once('../func/func_start.php');
require_once('../func/func_end.php');
require_once('../func/func_user.php');
require_once('../func/func_fetch.php');
require_once('../func/func_projects.php');
require_once('../func/func_misc.php');
require_once('../func/func_session.php');
require_once('../func/func_weeksdb.php');
require_once('../includes/config.php');

$action = clean($_REQUEST["action"]);

$xml_output = xmlIntro();
// Login always needed
require_once("../api/api_login.php");

if (isset($_SESSION['SESS_MEMBER_ID']))
{	
	switch ($action) {
		case "newperiod":
			require_once("../api/api_newperiod.php");	
			require_once("../api/api_currentperiod.php");
			break;
		case "startwork":
			$_REQUEST['type'] = "work";
			require_once("../api/api_newperiod.php");
			require_once("../api/api_currentperiod.php");
			break;
		case "endwork":
			$_REQUEST['type'] = "work";
			if($user->activetype == "break")
				require_once("../api/api_endbreak.php");
			else
				require_once("../api/api_ongoingperiod.php");
			break;
		case "currentperiod":
			require_once("../api/api_currentperiod.php");
			break;
		case "ongoingperiod":
			require_once("../api/api_ongoingperiod.php");
			require_once("../api/api_currentperiod.php");
			break;
		case "endbreak":
			require_once("../api/api_endbreak.php");
			require_once("../api/api_currentperiod.php");
			break;
		case "startbreak":
			$_REQUEST['type'] = "break";
			require_once("../api/api_ongoingperiod.php");
			require_once("../api/api_currentperiod.php");
			break;
		case "endbreak":
			require_once("../api/api_endbreak.php");
			require_once("../api/api_currentperiod.php");
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
		case "newproject":
			require_once("../api/api_newproject.php");
			break;
		case "attachproject":
			require_once("../api/api_attachproject.php");
			break;
		case "statistics":			// type
			require_once("../api/api_statisitcs.php");
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