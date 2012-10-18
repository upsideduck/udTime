<?php
/********************************************************************
 *
 *	getMonthStats - return month stats
 *
 *	Incomming: $year, $month
 *
 *	Outgoing : false or array(towork=>..,worked=>..)
 *
 ********************************************************************/ 
function getMonthStats($year,$month) {
	$result = mysql_query("SELECT towork, worked, timeoff, vacation FROM monthstats WHERE year = {$year} AND month = {$month}");
	$stats = mysql_fetch_assoc($result);
	return $stats;
}
/********************************************************************
 *
 *	getWeekStats - return week stats
 *
 *	Incomming: $year, $week
 *
 *	Outgoing : false or array(towork=>..,worked=>..)
 *
 ********************************************************************/ 
function getWeekStats($year,$week) {
	$result = mysql_query("SELECT towork, worked, timeoff, vacation FROM weekstats WHERE year = {$year} AND week = {$week}");
	$stats = mysql_fetch_assoc($result);
	return $stats;
}
?>