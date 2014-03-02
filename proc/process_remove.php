<?php
	//Start session
	session_start();
	
	require_once('../auth.php');
	
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_misc.php');
	require_once('../func/func_remove.php');
	require_once('../func/func_fetch.php');
	
	$result_arr = array();
	
	if($_GET['rtype'] == "break") {
		$result_arr = removeBreak($_GET['id']);
	} elseif($_GET['rtype'] == "work") {
		$result_arr = removeWork($_GET['id']);
	}

	$url = $_SERVER['HTTP_REFERER'].substr($_SERVER['REQUESTED_URI'],strpos($_SERVER['REQUESTED_URI'],'&')+1,0);

	if(!$result_arr[0]) {
		unset($result_arr[0]);
		$_SESSION['ERRMSG_ARR'] = $result_arr;
		session_write_close();
		header("location: $url");
		exit();
	}else{
		unset($result_arr[0]);
		$_SESSION['SUCCESS_ARR'] = $result_arr;
		session_write_close();
		header("location: $url");
		exit();
	}
?>