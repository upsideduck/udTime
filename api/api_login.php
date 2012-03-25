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
$username = clean($_REQUEST['username']);
$password = clean($_REQUEST['password']);

$result_arr = loginUser($username,$password);	
$xml_output .= resultXMLoutput($result_arr, "login");

if($result_arr[0]) {
	$qry="SELECT * FROM userdb WHERE username='$username' AND password='".md5($password)."'";
	$result=mysql_query($qry);
	start_session($result);
	$resultuser = mysql_query("SELECT member_id,username,timezone,dworkweek,activeperiod,activetype,offset FROM userdb WHERE username = '". $_SESSION['SESS_USERNAME']."'");
	$user = mysql_fetch_object($resultuser);
}

 ?>