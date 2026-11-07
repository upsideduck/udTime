<?php 
/************************************************************************
/*
/*	Adds a asworktime
/*
/*
/*	Requires: 			
/*	
/*	Post values:	
/*
/*	Output:			
/*
/************************************************************************/

$result_arr = null;

$starttime = clean($_REQUEST['starttime']);
$endtime = clean($_REQUEST['endtime']);
$type = clean($_REQUEST['type']);
if (strlen($_REQUEST['time']) == 8 && strpos($_REQUEST['time'],":") != false) $time = strtotime($_REQUEST['time']);
else $time = intval(clean($_REQUEST['time']));
if($time == 0 || !is_integer($time)) $time = 0;

$timespan = new timespan($starttime, $endtime);
$result_asworktime = $timespan->setasworktime($time, $type);

if($result_asworktime[0]){
	$i=0;
	foreach($result_asworktime as $result){
		$result_arr[0] = true;
		$result_arr[] = $result[1]." - ".$result[2];
		if ($result[3] > 0){
			$asworktime_sql = "SELECT a.date, a.time, UNIX_TIMESTAMP(a.modified) as modified, a.id, lookups.name FROM asworktime AS a JOIN lookups ON a.type = lookups.code WHERE a.member_id = {$_SESSION['SESS_MEMBER_ID']} AND a.id = {$result[3]} AND lookups.type = 'asworktime'";
			$asworktime_result = mysqli_query($link, $asworktime_sql);
			if($asworktime_result){
				while($asworktime_arr = mysqli_fetch_assoc($asworktime_result)){
					$output->arrays["asworktime"]["item".$i] = $asworktime_arr;	
					$i++;
				}
			}
		}
	}
}else{
	$result_arr[0] = false;
	$result_arr[] = "Nothing set";
	$result_arr[] = $result_asworktime[1];
	
}
$output->results['setasworktime'] = $result_arr;

?>