<?php
	//Start session
	session_start();
	
	require_once('../auth.php');
	
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_misc.php');
	require_once('../func/func_user.php');
	
	$newUser = array();
	$result_arr = array();
	
	//Sanitize the POST values
	$newUser['username'] = clean($_POST['username']);
	$newUser['ww'] = (int)$_POST['dworkweek'];
	$newUser['date'] = time();
	$newUser['password'] = clean($_POST['password']);
	$newUser['cpassword'] = clean($_POST['cpassword']);
	
	$result_arr = registerUser($newUser);
	
	//If there are input validations, redirect back to the registration form
	if(!$result_arr[0]) {
		unset($result_arr[0]);
		$_SESSION['ERRMSG_ARR'] = $result_arr;
		session_write_close();
		header("location: ../register.php");
		exit();
	}else{
		header("location: ../register-success.php");
		exit();
	}
?>