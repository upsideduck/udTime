<?php
//Start session
session_start();

require_once('../auth.php');

//Include database connection details
require_once('../includes/config.php');
require_once('../func/func_misc.php');
require_once('../func/func_fetch.php');

$output = new output();

require_once("../api/api_fetchperiods.php");

$periodsArray = $output->periods[0];
$asworktimes = $output->asworktimes;
$againstworktime = $output->againstworktime;

$monthstats = timespan::updatemonthStats($year,$month);

$output->results = array();
$output->periods = array();
$output->asworktimes = array();
$output->againstworktime = array();

$tooutput = array();
if($periodsArray){
	foreach($periodsArray as $period){
		$day =  date("j",$period[0]["starttime"]);
		$loopoutput['id'] = $period[0]["id"];
		$loopoutput['day'] = date("j",$period[0]["starttime"]);
		$loopoutput['date'] = date("d-M",$period[0]["starttime"]);
		$loopoutput['start'] = date("H:i:s",$period[0]["starttime"]);
		$loopoutput['end'] = date("H:i:s",$period[0]["endtime"]);
		$loopoutput['asworktime'] = $asworktimes[0]["days"][date("Y-m-d",$period[0]["starttime"])]["time"];
		unset($asworktimes[0]["days"][date("Y-m-d",$period[0]["starttime"])]);	//Necessary to afterwards see if we have days with just free/asworktime time and no work
		$loopoutput['againstworktime'] = $againstworktime[0]["days"][date("Y-m-d",$period[0]["starttime"])]["time"];
		unset($againstworktime[0]["days"][date("Y-m-d",$period[0]["starttime"])]);
		$breaktimeForPeriod = 0;
		if(count($period) > 1){
			for ($i = 1; $i < count($period); $i++) {
				$breaktimeForPeriod +=  $period[$i]['endtime'] - $period[$i]['starttime'];			
			}
		}
		$loopoutput['break'] = timestampToTime($breaktimeForPeriod);
		$loopoutput['worked'] = timestampToTime($period[0]["endtime"] - $period[0]["starttime"] - $breaktimeForPeriod);
		$loopoutput['workeddec'] = timestampToDecTime($period[0]["endtime"] - $period[0]["starttime"] - $breaktimeForPeriod);
		
		$tooutput["d".$day][] =  $loopoutput;
	}
}

$numdays = cal_days_in_month(CAL_GREGORIAN, $month, $year); 

for($i = 1; $i <= $numdays; $i++){
	$date = date("Y-m-d",strtotime($year."-".$month."-".$i));
	if($againstworktime[0]["days"][$date] != null || $asworktimes[0]["days"][$date] != null){
		
		$loopoutput['id'] = "";
		$loopoutput['day'] = $i;
		$loopoutput['date'] = date("d-M",strtotime($date));
		$loopoutput['start'] = "";
		$loopoutput['end'] = "";
		$loopoutput['break'] = "";
		if($againstworktime[0]["days"][$date] != null) $loopoutput['againstworktime'] = $againstworktime[0]["days"][$date]['time'];
		else $loopoutput['againstworktime'] = "";
		if($asworktimes[0]["days"][$date] != null) $loopoutput['asworktime'] = $asworktimes[0]["days"][$date]['time'];
		else $loopoutput['asworktime'] = "";
		$loopoutput['worked'] = "00:00:00";
		$loopoutput['workeddec'] = "0.00";
		$tooutput["d".$i][] =  $loopoutput;
	}
}

$output->arrays['monthview'] = $tooutput;


$sumworked = timestampToDecTime($monthstats['worked']);
$sumworkedinclvac = timestampToDecTime($monthstats['worked']+$monthstats['asworktime']['sum']);
$sumtowork = timestampToDecTime($monthstats['towork']);
$sumtoworkinclfree =timestampToDecTime($monthstats['towork']-$monthstats['againstworktime']['sum']);
$sumasworktime = timestampToDecTime($monthstats['asworktime']['sum']);
$sumagainstworktime = timestampToDecTime($monthstats['againstworktime']['sum']);
$fromdate = date("Y-m-d",strtotime($year."-".$month));
$todate = date("Y-m-d",strtotime($year."-".$month)+ (60*60*24*$numdays) - 1); 
$prevmonth = date("m", strtotime("{$year}-{$month} - 1 month"));
$prevyear = date("Y", strtotime("{$year}-{$month} - 1 month"));
$nextmonth = date("m", strtotime("{$year}-{$month} + 1 month"));
$nextyear = date("Y", strtotime("{$year}-{$month} + 1 month"));


$output->arrays['monthinfo'] = array("days"=>$numdays,"fromdate"=>$fromdate,"todate"=>$todate,"sumworked"=>$sumworked,"sumworkedinclvac"=>$sumworkedinclvac,"sumtowork"=>$sumtowork,"sumtoworkinclfree"=>$sumtoworkinclfree,"sumasworktime"=>$sumasworktime,"sumagainstworktime"=>$sumagainstworktime,"prevmonth"=>$prevmonth,"prevyear"=>$prevyear,"nextmonth"=>$nextmonth,"nextyear"=>$nextyear);

header('Content-type: text/xml'); 
echo  $output->outputToXml();
session_write_close();
exit();
?>