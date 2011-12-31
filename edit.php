<?php
	define(__SCRIPT_NAME__,"edit");
	
	require_once('auth.php');
    require_once("includes/config.php");
    require_once('func/func_fetch.php');
    require_once('func/func_misc.php');
    require_once('includes/header.php');

	echo "<h1 id='page_headline'>Edit</h1>";
	//<a href="index.php">Home</a> | <a href="profile.php">My Profile</a> | <a href="summary.php">Summary</a>
	notification();
	
	$inputId = $_GET["p"];
	$inputType = $_GET["type"];
	$inputAction = $_GET['a'];
	
	switch ($inputAction) {
		case "edit":
			if($inputType == "work") {
				$period = fetchWorkPeriod($inputId);
				$result_arr = $period[0];
			}
			elseif($inputType == "break") {
				$period = fetchBreakPeriod($inputId);
				$result_arr = $period[0];	
			}
			elseif($inputType == "holiday") {
				$result_arr[0] = true;
				$result_arr[] = "Edit ready";		
			} else {
				$result_arr[0] = false;
				$result_arr[] = "Nothing to edit";
			}
			break;
		case "new":
			$result_arr[0] = true;
			$result_arr[] = "New ready";
			break;
		default:
			$result_arr[0] = false;
			$result_arr[] = "Action missing";
			break;
	}
	
	if(($inputType == "work" || $inputType == "break") && $result_arr[0] && $inputAction == "edit") {
		require_once('includes/edit_form.php');
	}elseif(($inputType == "work" || $inputType == "break") && $result_arr[0] && $inputAction == "new") {
		require_once('includes/new_form.php');
	}elseif(($inputType == "holiday") && $result_arr[0]) {
		require_once('includes/holiday_form.php');
	}else{
		if(!$result_arr[0]) {
			unset($result_arr[0]);
			echo '<ul class="err">';
			foreach($result_arr as $msg) {
				echo '<li>',$msg,'</li>'; 
			}
			echo '</ul>';
		}
	}
	
	require_once('includes/footer.php');
?>