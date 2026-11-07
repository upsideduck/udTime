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


//Sanitize the POST values
	if(!is_numeric($_REQUEST["start_time"]) && !is_numeric($_REQUEST["end_time"])){	
   	 	$starttime = strtotime(clean($_REQUEST["start_time"]));
   	 	$endtime = strtotime(clean($_REQUEST["end_time"]));
   	}else{
   		$starttime = clean($_REQUEST["start_time"]);
   	 	$endtime = clean($_REQUEST["end_time"]);
   	}
$comment = clean($_REQUEST["comment"]);    
  
$output->results['setwork'] = addPeriod("work", $starttime, $endtime, $comment, $id);

if ($id > 0){
	$work_sql = "SELECT id, UNIX_TIMESTAMP(modified) as modified, starttime, endtime, comment FROM workdb WHERE member_id = {$_SESSION['SESS_MEMBER_ID']} AND id = {$id}";
	$work_result = mysqli_query($link, $work_sql);
	if($work_result){
		while($work_arr = mysqli_fetch_assoc($work_result)){
			$output->arrays["work"] = $work_arr;	
		}
	}
}
?>