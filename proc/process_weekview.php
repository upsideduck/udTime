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

$weekstats = timespan::updateWeekStats($year,$week);

$output->results = array();
$output->periods = array();
$output->asworktimes = array();
$output->againstworktime = array();


$tooutput = array();
if($periodsArray){
	foreach($periodsArray as $period){
		$day =  date("N",$period[0]["starttime"]);
		$loopoutput['id'] = $period[0]["id"];
		$loopoutput['day'] = date("N",$period[0]["starttime"]);
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


for($i = 1; $i <= 7; $i++){
	$date = date("Y-m-d",strtotime($year."W".$week." +".($i-1)."days"));
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
$output->arrays['weekview'] = $tooutput;

$sumworked = timestampToDecTime($weekstats['worked']);
$sumworkedinclvac = timestampToDecTime($weekstats['worked']+$weekstats['asworktime']['sum']);
$sumtowork = timestampToDecTime($weekstats['towork']);
$sumtoworkinclfree =timestampToDecTime($weekstats['towork']-$weekstats['againstworktime']['sum']);
$sumasworktime = timestampToDecTime($weekstats['asworktime']['sum']);
$sumagainstworktime = timestampToDecTime($weekstats['againstworktime']['sum']);
$fromdate = date("Y-m-d",strtotime($year."W".$week));
$todate = date("Y-m-d",strtotime($year."W".$week)+ (60*60*24*7) - 1); 
$prevweek = date("W", strtotime("{$year}W{$week} - 1 week"));
$prevyear = date("o", strtotime("{$year}W{$week} - 1 week"));
$nextweek = date("W", strtotime("{$year}W{$week} + 1 week"));
$nextyear = date("o", strtotime("{$year}W{$week} + 1 week"));

$output->arrays['weekinfo'] = array("days"=>7,"fromdate"=>$fromdate,"todate"=>$todate,"sumworked"=>$sumworked,"sumworkedinclvac"=>$sumworkedinclvac,"sumtowork"=>$sumtowork,"sumtoworkinclfree"=>$sumtoworkinclfree,"sumasworktime"=>$sumasworktime,"sumagainstworktime"=>$sumagainstworktime,"prevweek"=>$prevweek,"prevyear"=>$prevyear,"nextweek"=>$nextweek,"nextyear"=>$nextyear);

header('Content-type: text/xml'); 
echo $output->outputToXml();
session_write_close();
exit();
?>