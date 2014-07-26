<?php 

//Start session
session_start();

require_once('../includes/config.php');
require_once('../func/func_start.php');
require_once('../func/func_end.php');
require_once('../func/func_user.php');
require_once('../func/func_stats.php');
require_once('../func/func_edit.php');
require_once('../func/func_fetch.php');
require_once('../func/func_projects.php');
require_once('../func/func_misc.php');
require_once('../func/func_session.php');
require_once('../func/func_remove.php');

$actions = clean($_REQUEST["action"]);
$outputtype = clean($_REQUEST["output"]);

$output = new output();
// Login always needed
require_once("../api/api_login.php");

if (isset($_SESSION['SESS_MEMBER_ID']))
{	
	$action_arr = explode(",",$actions);
	foreach($action_arr as $action){
		switch ($action) {
			case "newperiod":
				require_once("../api/api_newperiod.php");	
				require_once("../api/api_currentperiod2.php");
				break;
			case "startwork":
				$_REQUEST['type'] = "work";
				require_once("../api/api_newperiod.php");
				require_once("../api/api_currentperiod2.php");
				break;
			case "endwork":
				$_REQUEST['type'] = "work";
				if($user->activetype == "break")
					require_once("../api/api_endbreak.php");
				else
					require_once("../api/api_ongoingperiod.php");
				require_once("../api/api_currentperiod2.php");
				break;
			case "currentperiod":
				require_once("../api/api_currentperiod.php");
				break;
			case "currentperiod2":
				require_once("../api/api_currentperiod2.php");
					
				break;
			case "ongoingperiod":
				require_once("../api/api_ongoingperiod.php");
				require_once("../api/api_currentperiod2.php");

				break;
			case "endbreak":
				require_once("../api/api_endbreak.php");
				require_once("../api/api_currentperiod2.php");
				break;
			case "startbreak":
				$_REQUEST['type'] = "break";
				require_once("../api/api_ongoingperiod.php");
				require_once("../api/api_currentperiod2.php");
				break;
			case "endbreak":
				require_once("../api/api_endbreak.php");
				require_once("../api/api_currentperiod2.php");
				break;
			case "setasworktime":
				require_once("../api/api_setasworktime.php");
				break;
			case "removeasworktime":
				require_once("../api/api_removeasworktime.php");
				break;
			case "setagainstworktime":
				require_once("../api/api_setagainstworktime.php");
				break;
			case "removeagainstworktime":
				require_once("../api/api_removeagainstworktime.php");
				break;
			case "setwork":
				require_once("../api/api_setwork.php");
				break;
			case "updatework":
				require_once("../api/api_updatework.php");
				break;
			case "removework":
				require_once("../api/api_removework.php");
				break;
			case "setbreak":
				require_once("../api/api_setbreak.php");
				break;
			case "updatebreak":
				require_once("../api/api_updatebreak.php");
				break;
			case "removebreak":
				require_once("../api/api_removebreak.php");
				break;			
			case "newproject":
				require_once("../api/api_newproject.php");
				break;
			case "attachproject":
				require_once("../api/api_attachproject.php");
				break;
			/****
			*	Incomming: stattype(r), statweek(o), statmonth(o), statyear(o) 
			*/
			case "statistics":			// type
				require_once("../api/api_statisitcs.php");
				break;
			case "weektotals":			// type
				require_once("../api/api_week_totals.php");
				break;
			case "weekdetail":
				require_once("../api/api_fetchperiods.php");
				require_once("../api/api_week_day.php");
				break;
			case "monthtotals":			// type
				require_once("../api/api_month_totals.php");
				break;
			case "fetchperiods":			// type
				require_once("../api/api_fetchperiods.php");
				break;
			case "serverupdates":			
				require_once("../api/api_serverupdates.php");
				break;
			default:
				break;
		}
	}

}


close_session();
if($outputtype == "json"){
	header('Content-type: text/json'); 
	echo $output->outputToJson();
}else{
	header('Content-type: text/xml'); 
	echo $output->outputToXml();	
}
session_write_close();
?>	