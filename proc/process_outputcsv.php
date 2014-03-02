<?php
//Start session
session_start();



//Include database connection details
require_once('includes/config.php');
require_once(__SITE_BASE__.'/auth.php');
require_once(__SITE_BASE__.'/func/func_misc.php');
require_once(__SITE_BASE__.'/func/func_fetch.php');

$year = $_REQUEST['y'];
$month = $_REQUEST['m'];
$numdays = cal_days_in_month(CAL_GREGORIAN, $month, $year); 
$timeArray = fetchStartEndTime("month",$year,$month);
$periodsArray = fetchPeriodsArray($timeArray);
$daysArray = array_fill(1,$numdays,null);

$monthstats = timespan::updateMonthStats($year,$month);
if(!$monthstats){
	echo "Stats not able to be calulated! Contact webmaster.";
	exit;
}

if($periodsArray){
	foreach($periodsArray as $period){
		$dayToAddTo = date("j",$period[0]["starttime"]);
		$daysArray[$dayToAddTo][] = $period;
	}
}

$prevmonth = date("m", strtotime("{$year}-{$month} - 1 month"));
$prevyear = date("Y", strtotime("{$year}-{$month} - 1 month"));
$nextmonth = date("m", strtotime("{$year}-{$month} + 1 month"));
$nextyear = date("Y", strtotime("{$year}-{$month} + 1 month"));

$tocsvarray = array(); 

for($i = 1; $i <= $numdays; $i++){
	$day = date("D",strtotime("{$year}-{$month}-{$i}"));
	
	if($asworktimetimestamp = $monthstats['asworktime']['days'][$year."-".$month."-".($i < 10 ? '0'.$i : $i)]['time']) $asworktimetime = timestampToTime($asworktimetimestamp);
	else $asworktimetime = "";
	if($againstworktimetimestamp = $monthstats['againstworktime']['days'][$year."-".$month."-".($i < 10 ? '0'.$i : $i)]['time']) $againstworktimetime = timestampToTime($againstworktimetimestamp);
	else $againstworktimetime = "";
	
	$periodsForDay = $daysArray[$i];
	$numPeriodsForDay = count($periodsForDay);
	if($periodsForDay){
		$writedate = true;
		foreach($periodsForDay as $periodForDay){
			$workTimePeriod = 0;
			$starttime = date("H:i",$periodForDay[0]['starttime']);
			$endtime = date("H:i",$periodForDay[0]['endtime']);
			
			$workTimePeriod = $workTimePeriod + ($periodForDay[0]['endtime'] - $periodForDay[0]['starttime']);
			
			$breaks = "";
			for($i2 = 1; $i2 < count($periodForDay); $i2++){
				$breaks .= date("H:i",$periodForDay[$i2]['starttime']) . "--" .  date("H:i",$periodForDay[$i2]['endtime']);
				$workTimePeriod = $workTimePeriod - ($periodForDay[$i2]['endtime'] - $periodForDay[$i2]['starttime']);
				if($i2 < count($periodForDay)) $breaks .= "<br>";
				
				if($i2 == 1)
					$tocsvarray[] = array($i,$day,$starttime,date("H:i",$periodForDay[$i2]['starttime']),date("H:i",$periodForDay[$i2]['endtime']),$endtime,$againstworktimetime,$asworktimetime);
				else
					$tocsvarray[] = array("","","",date("H:i",$periodForDay[$i2]['starttime']),date("H:i",$periodForDay[$i2]['endtime']),"","","");
			}
			
			if($breaks == "") 
				$tocsvarray[] = array($i,$day,$starttime,"","",$endtime,$againstworktimetime,$asworktimetime);

			
			$worktime = timestampToDecTime($workTimePeriod);
			
			$workTimeMonth += $workTimePeriod;
			
		}	
	}else{
			$tocsvarray[] = array($i,$day,"","","","",$againstworktimetime,$asworktimetime);	
	}
			
}


function outputCSV($data){
	$outputstream = fopen("php://output","w");
	function __outputCSV(&$vals,$key,$filehandler){
		fputcsv($filehandler, $vals,",",'"');
	}
	array_walk($data, '__outputCSV',$outputstream);
	fclose($outputstream);
}

header( 'Pragma: public' ); // required
header( 'Expires: 0' );
header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
header( 'Cache-Control: private', false ); // required for certain browsers 
header( 'Content-Type: text/csv' );

header( 'Content-Disposition: attachment; filename="month.csv";' );
header( 'Content-Transfer-Encoding: binary' );
//header( 'Content-Length: ' . filesize( $filename ) );

outputCSV($tocsvarray);

session_write_close();
exit;


