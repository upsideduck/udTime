<?php 
/************************************************************************
/*
/*	Adds or updates a holiday for a week
/*
/*
/*	Requires: 		func_weeksdb.php
/*					func_misc.php	
/*	
/*	Post values:	week, year, timestamp
/*
/*	Output:			$xml_output - $result_arr as xml
/*
/************************************************************************/

$result_arr = null;

$week = clean($_REQUEST['week']);
$year = clean($_REQUEST['year']);
$time = clean($_REQUEST['time']);

if (strlen(strstr($time,":"))>0) $timestamp = timeToTimestamp($time);
else $timestamp = $time;
$timestamp = intval($timestamp);

$result_arr = updateHoliday($week, $year, $timestamp);

$xml_output .= resultXMLoutput($result_arr, "holidayupdate");
?>