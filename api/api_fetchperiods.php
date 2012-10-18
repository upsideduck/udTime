<?php 
/************************************************************************
/*
/*	Fetch periods as xml
/*
/*
/*	Requires: 		func_fetch.php
/*					func_misc.php
/*	
/*	Post values:	
/*
/*	Output:			$output_xml - $result_arr as xml
/*
/************************************************************************/

$week = clean($_REQUEST['w']);
$month = clean($_REQUEST['m']);
$year = clean($_REQUEST['y']);
$result_arr = null;

if($month != "" ){
	$timearray = fetchStartEndTime("month",$year,$month);
}elseif($week != ""){
	$timearray = fetchStartEndTime("week",$year,"","",$week);
}else{
	$timearray = array('start' => 0, 'end' => 0);
}
$timespan = new timespan($timearray['start'], $timearray['end']);
$rarray = fetchPeriodsArray($timearray);
$rarray = $rarray[0]; 

if($rarray == null || $year == null || ($month == null && $week == null)){
	$result_arr[0] = false;
	$result_arr[] = "No periods fetched";	
	$xml_output .= resultXMLoutput($result_arr, "fetchperiods");
}else{
	$result_arr[0] = true;
	$result_arr[] = "Periods fetched";
	
	$xml_output .= resultXMLoutput($result_arr, "fetchperiods");
	foreach($rarray as $period){
		if(count($period) > 1){
			$xml_output .= "\t<period>\n";
			foreach (array_keys($period[0]) as $key) {
				$xml_output .= "\t\t<$key>".$period[0][$key]."</$key>\n";
			} 
			unset($period[0]);		
			foreach($period as $break){
				$xml_output .= arrayXMLoutput($break,"break");
			}
			$xml_output .= "\t</period>\n";
		}else{
			$xml_output .= arrayXMLoutput($period[0]);
		}
	}
}
$freedays = $timespan->getFreedays();
if(count($freedays['days']) > 0){
	foreach (array_keys($freedays['days']) as $date) {
		$xml_output .= "\t<freeday>\n";
		$xml_output .= "\t\t<id>".$freedays['days'][$date]['id']."</id>\n";
		$xml_output .= "\t\t<date>".$date."</date>\n";		
		$xml_output .= "\t\t<time>".$freedays['days'][$date]['time']."</time>\n";
		$xml_output .= "\t</freeday>\n";
	} 	
}
$vacationdays = $timespan->getVacationdays();
if(count($vacationdays['days']) > 0){
	foreach (array_keys($vacationdays['days']) as $date) {
		$xml_output .= "\t<vacationday>\n";
		$xml_output .= "\t\t<id>".$vacationdays['days'][$date]['id']."</id>\n";
		$xml_output .= "\t\t<date>".$date."</date>\n";		
		$xml_output .= "\t\t<time>".$vacationdays['days'][$date]['time']."</time>\n";
		$xml_output .= "\t</vacationday>\n";
	} 	
}

$userinforesults = mysql_query("SELECT statsstartdate,registerdate FROM userdb WHERE member_id = ". $_SESSION['SESS_MEMBER_ID']);
$userinfo = mysql_fetch_assoc($userinforesults);
$xml_output .= arrayXMLoutput($userinfo,"userinfo");
?>