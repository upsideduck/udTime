<?php 
/************************************************************************
/*
/*	Add new period
/*
/*
/*	Requires: 		func_start.php
/*					func_misc.php
/*	
/*	Post values:	type
/*					comment
/*					timestamp
/*
/*	Output:			$output_xml - $result_arr as xml
/*
/************************************************************************/

$result_arr = null;

//Sanitize the POST values
$type = clean($_REQUEST['type']);
$comment = clean($_POST['comment']);
if ($_REQUEST['timestamp'] == "") $timestamp = date("U");
elseif (strlen($_REQUEST['timestamp']) == 5) $timestamp = strtotime($_REQUEST['timestamp']);
else $timestamp = clean($_REQUEST['timestamp']);


$result_arr = goWork($type, $comment, $timestamp);
$output->results['newperiod'] = $result_arr;

 ?>