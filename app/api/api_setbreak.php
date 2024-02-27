<?php 

//Sanitize the POST values
if(!is_numeric($_REQUEST["start_time"]) && !is_numeric($_REQUEST["end_time"])){	
 	$starttime = strtotime(clean($_REQUEST["start_time"]));
  	$endtime = strtotime(clean($_REQUEST["end_time"]));
}else{
	$starttime = clean($_REQUEST["start_time"]);
   	$endtime = clean($_REQUEST["end_time"]);
}
$comment = clean($_REQUEST["comment"]);    
$parentId = clean($_REQUEST["pid"]);  
  
$output->results['setbreak'] = addPeriod("break", $starttime, $endtime, $comment, $id);

if ($id > 0){
	$break_sql = "SELECT id, UNIX_TIMESTAMP(modified) as modified, starttime, endtime, comment, parent_id FROM breakdb WHERE member_id = {$_SESSION['SESS_MEMBER_ID']} AND id = {$id}";
	$break_result = mysqli_query($link, $break_sql);
	if($break_result){
		while($break_arr = mysqli_fetch_assoc($break_result)){
			$output->arrays["break"] = $break_arr;	
		}
	}
}

?>