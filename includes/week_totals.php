<?php

//Start session
if(!isset($_SESSION['SESS_MEMBER_ID'])) session_start();
require_once($_SERVER["DOCUMENT_ROOT"]."/time/includes/config.php");
require_once(__SITE_BASE__."func/func_weeksdb.php");
require_once(__SITE_BASE__."func/func_fetch.php");
require_once(__SITE_BASE__."func/func_misc.php");


$weeksResult = fetchWeeksSummary();
$all_weeks_totals = 0;
while($weeksReport = mysql_fetch_object($weeksResult)) {
	if(date("W-y") != $weeksReport->week."-".$weeksReport->year) $status = "<img src='images/check.png' height='10'>\n";
	else {
		$status = " ongoing...";
		$active_week = true;
	}
	
	if($weeksReport->holiday != 0) $holiday = " + <a href='summary.php?summary=week_list&w=$weeksReport->week&y=$weeksReport->year'>". timestampToTime($weeksReport->holiday)."</a>";
	else $holiday = "";
	
	$diffstamp = ($weeksReport->holiday+$weeksReport->total - $user->workweek);
	if ($active_week) {
		$diff = " --- ";
		$diffstamp = 0;
	}
	elseif ($diffstamp < 0) $diff = "-".timestampToTime(abs($diffstamp));
	else $diff = "+".timestampToTime($diffstamp);
	
	$all_weeks_totals += $diffstamp;
	
	printf("<div = class='weeklist_item'><div class='weeklist_date'>w%s - y%s: </div><div class='weeklist_timestamp'><a href='summary.php?summary=week_list&w=%s&y=%s'>%s</a></div><div class='weeklist_holiday'>%s</div><div class='weeklist_diff'>%s</div><div class='weeklist_status'>%s</div></div>",$weeksReport->week, $weeksReport->year,$weeksReport->week, $weeksReport->year,timestampToTime($weeksReport->total), $holiday, $diff , $status );
}
echo "<div id='weeklist_alltotals'>";
if ($all_weeks_totals < 0) echo "-".timestampToTime(abs($all_weeks_totals));
else timestampToTime($all_weeks_totals);
echo "</div>";

?>