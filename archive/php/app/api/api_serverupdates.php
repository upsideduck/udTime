<?php 
$result_arr = null;
$modafter= clean($_REQUEST['modafter']);
$result_arr[0] = true;

if($modafter <> ""){
	$work_sql = "SELECT id, UNIX_TIMESTAMP(modified) as modified, starttime, endtime, comment FROM workdb WHERE member_id = {$_SESSION['SESS_MEMBER_ID']} AND UNIX_TIMESTAMP(modified) >= {$modafter}";
	$work_result = mysqli_query($link, $work_sql);
	if($work_result){
		while($work_arr = mysqli_fetch_assoc($work_result)){
			$output->arrays["work"][] = $work_arr;	
		}
	}
	
	$break_sql = "SELECT id, UNIX_TIMESTAMP(modified) as modified, starttime, endtime, comment, parent_id FROM breakdb WHERE member_id = {$_SESSION['SESS_MEMBER_ID']} AND UNIX_TIMESTAMP(modified) >= {$modafter}";
	$break_result = mysqli_query($link, $break_sql);
	if($break_result){
		while($break_arr = mysqli_fetch_assoc($break_result)){
			$output->arrays["break"][] = $break_arr;	
		}
	}
	
	$asworktime_sql = "SELECT a.date, a.time, UNIX_TIMESTAMP(a.modified) as modified, a.id, lookups.name FROM asworktime AS a JOIN lookups ON a.type = lookups.code WHERE a.member_id = {$_SESSION['SESS_MEMBER_ID']} AND UNIX_TIMESTAMP(a.modified) >= {$modafter} AND lookups.type = 'asworktime'";
	$asworktime_result = mysqli_query($link, $asworktime_sql);
	if($asworktime_result){
		while($asworktime_arr = mysqli_fetch_assoc($asworktime_result)){
			$output->arrays["asworktime"][] = $asworktime_arr;	
		}
	}
	
	$againstworktime_sql = "SELECT a.date, a.time,UNIX_TIMESTAMP(a.modified) as modified, a.id, lookups.name FROM againstworktime AS a JOIN lookups ON a.type = lookups.code WHERE a.member_id = {$_SESSION['SESS_MEMBER_ID']} AND UNIX_TIMESTAMP(a.modified) >= {$modafter} AND lookups.type = 'againstworktime'";
	$againstworktime_result = mysqli_query($link, $againstworktime_sql);
	if($againstworktime_result){
		while($againstworktime_arr = mysqli_fetch_assoc($againstworktime_result)){
			$output->arrays["againstworktime"][] = $againstworktime_arr;	
		}
	}
	
	$deleted_sql = "SELECT original_id, type, UNIX_TIMESTAMP(modified) as modified FROM deleted WHERE member_id = {$_SESSION['SESS_MEMBER_ID']} AND UNIX_TIMESTAMP(modified) >= {$modafter}";
	$deleted_result = mysqli_query($link, $deleted_sql);
	if($deleted_result){
		while($deleted_arr = mysqli_fetch_assoc($deleted_result)){
			$output->arrays["deleted"][] = $deleted_arr;	
		}
	}
	$result_arr[] = "Fetched modified entries";
}else{
	$result_arr[0] = false;
	$result_arr[] = "No modified date specified";
	
}

$output->results['serverupdates'] = $result_arr;
?>