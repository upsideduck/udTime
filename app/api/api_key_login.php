<?php 
/************************************************************************
/*
/*	Login script that outputs the result in xml
/*  and starts a session if login was successful
/*
/*
/*	Requires: 		func_users.php
/*					func_misc.php
/*					func_session.php
/*	
/*	Post values:	username
/*					password
/*
/*	Output:			$output_xml - $result_arr as xml
/*
/************************************************************************/

$result_arr = null;

//Sanitize the POST values
$api_key = clean($_REQUEST['api_key']);

$result_arr = verifyApikey($api_key);	
$output->results['login'] = $result_arr;

if($result_arr[0]) {
	$api_key_hash = md5($api_key);
	$qry_apikey="SELECT * FROM apikeys WHERE key_hash='$api_key_hash'";
	$result_apikey=mysqli_query($link, $qry_apikey);
	$api_key_obj = mysqli_fetch_object($result_apikey);

	$qry="SELECT * FROM userdb WHERE member_id='$api_key_obj->member_id'";
	$result=mysqli_query($link, $qry);
	start_session($result);
	$resultuser = mysqli_query($link, "SELECT member_id,username,timezone,dworkweek,activeperiod,activetype,offset,registerdate,statsstartdate FROM userdb WHERE username = '". $_SESSION['SESS_USERNAME']."'");
	$user = mysqli_fetch_object($resultuser);
}

 ?>